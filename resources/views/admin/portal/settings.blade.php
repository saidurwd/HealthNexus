@extends('layouts.adminlte')

@section('page_title', __('Portal settings'))

@section('page_content')
    @include('admin.portal._flash')
    <p class="text-muted small">{{ __('These are starting values. Management, the privacy officer and a lawyer should confirm them before the portal opens to patients.') }}</p>
    <form method="POST" action="{{ route('admin.portal.settings.save') }}" class="card"><div class="card-body row g-3">@csrf
        <div class="col-md-4"><label class="form-label">{{ __('A new result is shown to the patient') }}</label><select name="release_default_mode" class="form-select">@foreach(['immediate' => 'Straight away', 'after_review' => 'After a clinician releases it', 'delayed' => 'After a delay', 'never' => 'Not online'] as $k => $t)<option value="{{ $k }}" @selected($v['release_default_mode'] === $k)>{{ __($t) }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">{{ __('Delay (hours) when delayed') }}</label><input type="number" name="release_default_delay_hours" value="{{ $v['release_default_delay_hours'] }}" class="form-control" min="0" max="720"></div>
        <div class="col-md-4"><label class="form-label">{{ __('Document links last (days)') }}</label><input type="number" name="link_ttl_days" value="{{ $v['link_ttl_days'] }}" class="form-control" min="1" max="60"></div>
        <div class="col-md-3"><label class="form-label">{{ __('Quiet hours start') }}</label><input name="quiet_start" value="{{ $v['quiet_start'] }}" class="form-control" placeholder="21:00"></div>
        <div class="col-md-3"><label class="form-label">{{ __('Quiet hours end') }}</label><input name="quiet_end" value="{{ $v['quiet_end'] }}" class="form-control" placeholder="08:00"></div>
        <div class="col-md-3"><label class="form-label">{{ __('Marketing messages per week, at most') }}</label><input type="number" name="max_marketing_per_week" value="{{ $v['max_marketing_per_week'] }}" class="form-control" min="0" max="14"></div>
        <div class="col-md-3"><label class="form-label">{{ __('Terms version') }}</label><input name="terms_version" value="{{ $v['terms_version'] }}" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">{{ __('Earliest booking (hours ahead)') }}</label><input type="number" name="min_lead_hours" value="{{ $v['min_lead_hours'] }}" class="form-control" min="0" max="72"></div>
        <div class="col-md-3"><label class="form-label">{{ __('No online change within (hours of visit)') }}</label><input type="number" name="change_cutoff_hours" value="{{ $v['change_cutoff_hours'] }}" class="form-control" min="0" max="168"></div>
        <div class="col-md-3"><label class="form-label">{{ __('Wrong passwords before locking') }}</label><input type="number" name="max_failures" value="{{ $v['max_failures'] }}" class="form-control" min="3" max="20"></div>
        <div class="col-md-3"><label class="form-label">{{ __('Locked for (minutes)') }}</label><input type="number" name="lock_minutes" value="{{ $v['lock_minutes'] }}" class="form-control" min="1" max="1440"></div>
    </div><div class="card-footer"><button class="btn btn-primary">{{ __('Save') }}</button></div></form>
@stop
