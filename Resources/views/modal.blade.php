<form action="{{ $formAction }}" method="GET" target="_blank" class="mfsprint-form">
    <input type="hidden" name="print" value="1">
    @if ($folderId)
        <input type="hidden" name="folder_id" value="{{ $folderId }}">
    @endif

    <p>{{ __('Leave out of the printout:') }}</p>
    <div class="checkbox">
        <label><input type="checkbox" name="exclude_notes" value="1" @if ($defaults['exclude_notes']) checked @endif> {{ __('Internal notes') }}</label>
    </div>
    <div class="checkbox">
        <label><input type="checkbox" name="exclude_history" value="1" @if ($defaults['exclude_history']) checked @endif> {{ __('History (status changes, assignments and other activity lines)') }}</label>
    </div>
    @if ($translatorActive)
        <div class="checkbox">
            <label><input type="checkbox" name="exclude_translations" value="1" @if ($defaults['exclude_translations']) checked @endif> {{ __('Translations') }}</label>
        </div>
    @endif

    <div class="margin-top">
        <button type="submit" class="btn btn-primary mfsprint-submit"><i class="glyphicon glyphicon-print"></i> {{ __('Print') }}</button>
        <button type="button" class="btn btn-link" data-dismiss="modal">{{ __('Cancel') }}</button>
    </div>
</form>
