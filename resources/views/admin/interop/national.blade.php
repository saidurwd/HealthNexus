@extends('layouts.adminlte')

@section('page_title', __('National exchange'))

@section('page_content')
    @include('admin.interop._flash')
    @if(! $exchange)<div class="alert alert-warning">{{ __('The national exchange has not been set up as a partner yet. Add a partner of the kind “National exchange” first.') }}</div>@endif
    @if($driver !== 'sandbox')<div class="alert alert-secondary">{{ __('No connection to the national exchange is installed yet, so a shared record waits in the queue and fails after the last try. Onboarding with the national authority is needed first.') }}</div>@else<div class="alert alert-info">{{ __('A test connection is on: records are kept for a day inside this system and go nowhere else.') }}</div>@endif
    <form method="POST" action="{{ route('admin.interop.national.share') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Share a patient\'s record') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-4"><label class="form-label" for="pn">{{ __('Patient number') }}</label><input id="pn" name="patient_no" class="form-control" required value="{{ old('patient_no') }}"></div>
        <div class="col-md-8"><label class="form-label" for="ov">{{ __('Emergency reason (only if the patient has not agreed and cannot be asked)') }}</label><input id="ov" name="override_reason" class="form-control" maxlength="200" value="{{ old('override_reason') }}"></div></div>
        <div class="card-footer"><button class="btn btn-primary" @disabled(! $exchange)>{{ __('Share') }}</button> <span class="text-muted small">{{ __('The patient must have agreed. An override needs a special right and is written to the log for the privacy officer.') }}</span></div></form>
@stop
