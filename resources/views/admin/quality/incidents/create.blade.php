@extends('layouts.adminlte')

@section('page_title', __('Report an incident'))

@section('page_content')
    @include('admin.quality._flash')
    <div class="alert alert-info small">{{ __('Report what happened, including things that almost happened. This is for learning, not for blame. Do not write the patient\'s name here; use the patient number if the patient was involved.') }}</div>
    <form method="POST" action="{{ route('admin.quality.incidents.store') }}" class="card"><div class="card-body row g-3">@csrf
        <div class="col-md-4"><label class="form-label">{{ __('What are you reporting?') }}</label><select name="kind" class="form-select" required>@foreach(['incident' => 'Something went wrong', 'near_miss' => 'A near miss: it was caught in time', 'adverse_event' => 'A patient was harmed'] as $k => $l)<option value="{{ $k }}" @selected(old('kind') === $k)>{{ __($l) }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">{{ __('Kind of event') }}</label><select name="category" class="form-select" required>@foreach($policy->list('incident.categories') as $c)<option value="{{ $c }}" @selected(old('category') === $c)>{{ __(\Illuminate\Support\Str::headline($c)) }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">{{ __('How much harm did it cause?') }}</label><select name="harm" class="form-select" required>@foreach($policy->list('incident.harm') as $h)<option value="{{ $h }}" @selected(old('harm', 'none') === $h)>{{ __(\Illuminate\Support\Str::headline($h)) }}</option>@endforeach</select><div class="form-text">{{ __('A near miss is recorded as no harm.') }}</div></div>
        <div class="col-md-4"><label class="form-label">{{ __('When did it happen?') }}</label><input type="datetime-local" name="occurred_at" class="form-control" value="{{ old('occurred_at', now('Asia/Dhaka')->format('Y-m-d\TH:i')) }}" required></div>
        <div class="col-md-4"><label class="form-label">{{ __('Department') }}</label><select name="department_id" class="form-select"><option value="">—</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected((string) old('department_id') === (string) $d->id)>{{ $d->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">{{ __('Where exactly?') }}</label><input name="location" class="form-control" maxlength="120" value="{{ old('location') }}" placeholder="{{ __('Ward 3, bed 12') }}"></div>
        <div class="col-12"><label class="form-label">{{ __('What happened?') }}</label><textarea name="description" class="form-control" rows="5" maxlength="4000" required>{{ old('description') }}</textarea></div>
        <div class="col-12"><label class="form-label">{{ __('What was done straight away?') }}</label><textarea name="immediate_action" class="form-control" rows="2" maxlength="1000">{{ old('immediate_action') }}</textarea></div>
        <div class="col-md-4"><label class="form-label">{{ __('Patient number (if a patient was involved)') }}</label><input name="patient_no" class="form-control" maxlength="40" value="{{ old('patient_no') }}"></div>
        @if($policy->bool('incident.allow_anonymous', true))<div class="col-md-8 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="anonymous" value="1" id="anon" @checked(old('anonymous'))><label class="form-check-label" for="anon">{{ __('Report without my name. Nobody will be able to see who sent it.') }}</label></div></div>@endif
    </div><div class="card-footer"><button class="btn btn-danger">{{ __('Send the report') }}</button></div></form>
@stop
