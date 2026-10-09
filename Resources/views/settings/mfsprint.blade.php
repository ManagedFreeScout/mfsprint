<div class="row">
    <div class="col-xs-12">
        <div class="alert alert-info">
            <strong>{{ __('MFS Print') }}</strong> &mdash; {{ __('Choose what to leave out when you print a conversation: internal notes, history and translations.') }}
            {{ __('A licence is a yearly subscription for one FreeScout installation, for all your agents.') }}
            <a href="{{ config('mfsprint.buy_url') }}" target="_blank" rel="noopener">{{ __('Buy or renew a licence') }}</a>
        </div>
    </div>
</div>

@php
    $mfsprintLicense = app(\Modules\MFSPrint\Services\LicenseService::class)->getLicenseStatus();
@endphp
@include('mfsprint::settings.partials.license', ['settings' => array_merge($settings, ['license_status' => $mfsprintLicense])])

@if ($mfsprintLicense['valid'] ?? false)
<div class="row">
    <div class="col-xs-12">
        <form class="form-horizontal margin-top" method="POST" action="">
            {{ csrf_field() }}
            <div class="panel panel-default">
                <div class="panel-heading">{{ __('Print defaults') }}</div>
                <div class="panel-body">
                    <p class="text-help">{{ __('Ticked here = ticked when an agent opens the print options. Agents can still change them each time.') }}</p>
                    @php
                        $mfsprintDefaults = [
                            'exclude_notes'   => __('Leave out internal notes'),
                            'exclude_history' => __('Leave out history'),
                        ];
                        if (\Modules\MFSPrint\Providers\MFSPrintServiceProvider::translatorActive()) {
                            $mfsprintDefaults['exclude_translations'] = __('Leave out translations (Ticket Translator)');
                        }
                    @endphp
                    @foreach ($mfsprintDefaults as $param => $label)
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-10">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="settings[mfsprint.default_{{ $param }}]" value="1" @if (!empty($settings['mfsprint.default_' . $param])) checked @endif>
                                        {{ $label }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
