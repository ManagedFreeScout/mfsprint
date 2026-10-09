<?php

namespace Modules\MFSPrint\Providers;

use App\Thread;
use Illuminate\Support\ServiceProvider;
use Modules\MFSPrint\Services\LicenseService;

defined('MFSPRINT_MODULE') || define('MFSPRINT_MODULE', 'mfsprint');

/**
 * MFS Print (freescout-modules card #301): the conversation's Print menu item opens a small
 * modal to leave out internal notes, history lines and (with Ticket Translator) translations,
 * then opens FreeScout's own print view with those threads filtered out on the server.
 * Rebuilt from the former Advanced Print module; nothing of its licensing is kept.
 */
class MFSPrintServiceProvider extends ServiceProvider
{
    const MODULE_ALIAS = 'mfsprint';

    // Query parameters on FreeScout's print view (conversation URL + print=1).
    const PARAMS = ['exclude_notes', 'exclude_history', 'exclude_translations'];

    protected $defer = false;

    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', self::MODULE_ALIAS);
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', self::MODULE_ALIAS);
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->registerSettings();
        $this->registerLicenseHooks();
        $this->registerPrintHooks();
    }

    public function register()
    {
        $this->app->singleton(LicenseService::class, function () {
            return new LicenseService();
        });
    }

    /** True when FreeScout's Ticket Translator module is active (its translations can be left out). */
    public static function translatorActive(): bool
    {
        return class_exists('\Module') && (bool) \Module::isActive('tickettranslator');
    }

    /**
     * Manage → Settings → MFS Print: licence panel plus the modal's default choices.
     * Only the three option keys are returned as settings, so FreeScout's own save stores just
     * those (an unticked box is removed and falls back to "off").
     */
    protected function registerSettings()
    {
        \Eventy::addFilter('settings.sections', function ($sections) {
            $sections[self::MODULE_ALIAS] = [
                'title'       => __('MFS Print'),
                'icon'        => 'print',
                'order'       => 320,
                'description' => __('Licence and print defaults for MFS Print.'),
            ];
            return $sections;
        }, 15);

        \Eventy::addFilter('settings.section_settings', function ($settings, $section) {
            if ($section !== self::MODULE_ALIAS) {
                return $settings;
            }
            foreach (self::PARAMS as $param) {
                $settings['mfsprint.default_' . $param] = (bool) \Option::get('mfsprint.default_' . $param, false);
            }
            return $settings;
        }, 20, 2);

        \Eventy::addFilter('settings.view', function ($view, $section) {
            return $section === self::MODULE_ALIAS ? self::MODULE_ALIAS . '::settings.mfsprint' : $view;
        }, 20, 2);
    }

    /**
     * Licence shown on Manage → Modules and re-checked every 6 hours, as in MFSEssentials (card #194).
     * The real gate is LicenseService::isLicensed() in every hook and in the controller.
     */
    protected function registerLicenseHooks()
    {
        \Eventy::addFilter('modules.show_license', function ($show, $module) {
            return (isset($module['alias']) && $module['alias'] === self::MODULE_ALIAS) ? true : $show;
        }, 20, 2);

        \Eventy::addFilter('modules.license_info', function ($license_info, $module_alias) {
            if ($module_alias !== self::MODULE_ALIAS) {
                return $license_info;
            }
            $status = app(LicenseService::class)->getLicenseStatus();
            return [
                'license'      => $status['license_key'] ?? '',
                'activated'    => $status['valid'] ?? false,
                'status'       => $status['status'] ?? 'inactive',
                'expires_at'   => $status['expires_at'] ?? null,
                'license_type' => $status['license_type'] ?? null,
            ];
        }, 20, 2);

        \Eventy::addFilter('module.requires_license', function ($requires, $module) {
            return (isset($module['alias']) && $module['alias'] === self::MODULE_ALIAS) ? true : $requires;
        }, 20, 2);

        \Eventy::addAction('schedule', function ($schedule) {
            $schedule->call(function () {
                $service = app(LicenseService::class);
                $status  = $service->getLicenseStatus();
                if (!empty($status['license_key'])) {
                    $service->validateLicense($status['license_key']);
                }
            })->cron('0 */6 * * *');
        }, 20, 1);
    }

    protected function registerPrintHooks()
    {
        // Print menu item -> our options modal. Without a licence FreeScout's own Print stays as it is.
        \Eventy::addFilter('conversation.get_action_buttons', function ($actions, $conversation, $user, $mailbox) {
            if (!isset($actions['print']) || !LicenseService::isLicensed()) {
                return $actions;
            }
            $actions['print']['url'] = function () use ($conversation) {
                return route('mfsprint.modal', ['id' => $conversation->id, 'folder_id' => request()->input('folder_id')]);
            };
            $actions['print']['attrs'] = [
                'data-trigger'     => 'modal',
                'data-modal-title' => __('Print'),
                'data-modal-size'  => 'sm',
                // The modal has its own Print / Cancel buttons; no second footer with Close.
                'data-modal-no-footer' => 'true',
            ];
            return $actions;
        }, 20, 4);

        // FreeScout's print view (?print=1): leave out what the agent ticked. FreeScout itself
        // opens the browser's print dialog on this page; nothing to add here.
        \Eventy::addFilter('conversation.view.threads', function ($threads) {
            $request = request();
            if (!$request->input('print') || !LicenseService::isLicensed()) {
                return $threads;
            }
            $excludeNotes   = (bool) $request->input('exclude_notes');
            $excludeHistory = (bool) $request->input('exclude_history');

            if ($excludeNotes || $excludeHistory) {
                $threads = $threads->filter(function ($thread) use ($excludeNotes, $excludeHistory) {
                    if ($excludeNotes && $thread->type == Thread::TYPE_NOTE) {
                        return false;
                    }
                    if ($excludeHistory && $thread->type == Thread::TYPE_LINEITEM) {
                        return false;
                    }
                    return true;
                })->values();
            }

            // Ticket Translator shows a thread's translations from threads.translations (its own
            // column). Emptying it on these in-memory models hides them in this view only; nothing
            // is saved.
            if ($request->input('exclude_translations') && self::translatorActive()) {
                foreach ($threads as $thread) {
                    if (array_key_exists('translations', $thread->getAttributes())) {
                        $thread->setAttribute('translations', null);
                    }
                }
            }

            return $threads;
        });

        // Print view only: say what was left out, so a printed copy is never mistaken for the full conversation.
        \Eventy::addAction('conversation.after_subject_block', function ($conversation, $mailbox) {
            $request = request();
            if (!$request->input('print') || !LicenseService::isLicensed()) {
                return;
            }
            $left = [];
            if ($request->input('exclude_notes')) {
                $left[] = __('internal notes');
            }
            if ($request->input('exclude_history')) {
                $left[] = __('history');
            }
            if ($request->input('exclude_translations') && self::translatorActive()) {
                $left[] = __('translations');
            }
            if ($left) {
                echo '<div class="mfsprint-left-out" style="padding: 4px 20px 10px; font-size: 12px; color: #72808e;">' . e(__('Printed without: :items.', ['items' => implode(', ', $left)])) . '</div>';
            }
        }, 20, 2);
    }
}
