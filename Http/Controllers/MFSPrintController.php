<?php

namespace Modules\MFSPrint\Http\Controllers;

use App\Conversation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MFSPrint\Providers\MFSPrintServiceProvider;
use Modules\MFSPrint\Services\LicenseService;

class MFSPrintController extends Controller
{
    /**
     * Print options modal, opened from the conversation's Print menu item.
     * Submitting it opens FreeScout's own print view (?print=1) with the chosen exclusions.
     */
    public function modal($id)
    {
        if (!LicenseService::isLicensed()) {
            abort(404);
        }

        $conversation = Conversation::find($id);
        if (!$conversation) {
            abort(404);
        }
        $user = auth()->user();
        // Same check as FreeScout's own conversation view.
        if (!$user->can('view', $conversation)) {
            abort(403);
        }

        $translatorActive = MFSPrintServiceProvider::translatorActive();

        return view('mfsprint::modal', [
            'formAction'       => route('conversations.view', ['id' => $conversation->id]),
            'folderId'         => request()->input('folder_id', $conversation->folder_id),
            'translatorActive' => $translatorActive,
            'defaults'         => [
                'exclude_notes'        => (bool) \Option::get('mfsprint.default_exclude_notes', false),
                'exclude_history'      => (bool) \Option::get('mfsprint.default_exclude_history', false),
                'exclude_translations' => $translatorActive && (bool) \Option::get('mfsprint.default_exclude_translations', false),
            ],
        ]);
    }

    /**
     * This module's own settings-page licence form (Activate/Deactivate buttons in
     * Resources/views/settings/partials/license.blade.php).
     */
    public function manageLicense(Request $request)
    {
        $licenseService = app(LicenseService::class);

        if ($licenseService->getLicenseStatus()['status'] === 'no_table') {
            return response()->json(['status' => 'error', 'message' => __('Licence table missing. Run: php artisan migrate')]);
        }

        $action     = $request->input('action');
        $licenseKey = $request->input('license_key');

        if (empty($licenseKey) && $action !== 'deactivate') {
            return response()->json(['status' => 'error', 'message' => __('Licence key is required.')]);
        }

        switch ($action) {
            case 'activate':
                $result = $licenseService->activateLicense($licenseKey);
                break;
            case 'deactivate':
                $licenseKey = $licenseService->getLicenseStatus()['license_key'] ?? $licenseKey;
                $result = $licenseService->deactivateLicense($licenseKey);
                break;
            default:
                return response()->json(['status' => 'error', 'message' => __('Invalid action.')]);
        }

        return response()->json([
            'status'  => $result['success'] ? 'success' : 'error',
            'message' => $result['message'],
        ]);
    }

    /**
     * FreeScout core's licence button on Manage → Modules posts here.
     */
    public function handleModuleLicenseAction(Request $request)
    {
        if ($request->input('module_alias') !== MFSPrintServiceProvider::MODULE_ALIAS) {
            return response()->json(['success' => false, 'message' => __('Invalid module')]);
        }

        $action     = $request->input('action');
        $licenseKey = $request->input('license_key');

        if (empty($licenseKey) && $action !== 'deactivate') {
            return response()->json(['success' => false, 'message' => __('Licence key is required.')]);
        }

        return response()->json(app(LicenseService::class)->performAction($action, $licenseKey));
    }
}
