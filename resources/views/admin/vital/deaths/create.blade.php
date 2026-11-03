@extends('layouts.adminlte')

@section('page_title', __('Write a death certificate'))

@section('page_content')
    @include('admin.vital._flash')
    <form method="POST" action="{{ route('admin.vital.deaths.store') }}" class="card">@csrf
        <div class="card-body row g-3">
            <div class="col-md-6"><label class="form-label" for="d-patient">{{ __('Patient') }}</label><x-ui.patient-select name="patient_id" id="d-patient" :value="$patient?->id" required /></div>
            <div class="col-md-3"><label class="form-label" for="d-died">{{ __('Date and time of death') }}</label><input id="d-died" type="datetime-local" name="died_at" value="{{ old('died_at', $diedAt?->timezone('Asia/Dhaka')->format('Y-m-d\TH:i')) }}" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label" for="d-place">{{ __('Place of death') }}</label><select id="d-place" name="place_of_death" class="form-select">@foreach(config('vital.places') as $x)<option value="{{ $x }}" @selected(old('place_of_death') === $x)>{{ __(\Illuminate\Support\Str::headline($x)) }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label" for="d-ward">{{ __('Ward or area') }}</label><input id="d-ward" name="ward_or_area" value="{{ old('ward_or_area') }}" class="form-control" maxlength="80"></div>
            <div class="col-md-5"><label class="form-label" for="d-manner">{{ __('Manner of death') }}</label><select id="d-manner" name="manner" class="form-select" required>@foreach(config('vital.manners') as $x)<option value="{{ $x }}" @selected(old('manner', 'natural') === $x)>{{ __(\Illuminate\Support\Str::headline($x)) }}</option>@endforeach</select>
                <div class="form-text">{{ __('Any manner other than natural is a police matter: a police case is opened with the certificate and the certificate is not given to the family until it is closed.') }}</div></div>
            <div class="col-12"><hr class="my-1"><div class="fw-semibold">{{ __('Cause of death') }}</div><div class="small text-muted">{{ __('Part I: line a is the direct cause; each line below it is the cause of the line above. Give a line only if the one above it is given.') }}</div></div>
            @foreach(['a' => 'a. Direct cause', 'b' => 'b. Due to (or as a consequence of)', 'c' => 'c. Due to (or as a consequence of)', 'd' => 'd. Due to (or as a consequence of)'] as $k => $label)
                <div class="col-md-8"><label class="form-label" for="d-cause-{{ $k }}">{{ __($label) }}</label><input id="d-cause-{{ $k }}" name="cause_{{ $k }}" value="{{ old('cause_'.$k) }}" class="form-control" maxlength="500" @required($k === 'a')></div>
                <div class="col-md-4"><label class="form-label" for="d-int-{{ $k }}">{{ __('Time between onset and death') }}</label><input id="d-int-{{ $k }}" name="interval_{{ $k }}" value="{{ old('interval_'.$k) }}" class="form-control" maxlength="40" placeholder="{{ __('e.g. 2 days') }}"></div>
            @endforeach
            <div class="col-md-8"><label class="form-label" for="d-contrib">{{ __('Part II: other conditions that contributed') }}</label><input id="d-contrib" name="contributing" value="{{ old('contributing') }}" class="form-control" maxlength="500"></div>
            <div class="col-md-4"><label class="form-label" for="d-icd">{{ __('Underlying cause (ICD-10 code)') }}</label><input id="d-icd" name="underlying_icd10" value="{{ old('underlying_icd10') }}" class="form-control" maxlength="12"></div>
            <div class="col-md-4"><label class="form-label" for="d-preg">{{ __('Pregnancy (women of child-bearing age)') }}</label><select id="d-preg" name="pregnancy_status" class="form-select"><option value=""></option>@foreach(config('vital.pregnancy_statuses') as $x)<option value="{{ $x }}" @selected(old('pregnancy_status') === $x)>{{ __(\Illuminate\Support\Str::headline($x)) }}</option>@endforeach</select></div>
            <div class="col-md-4 d-flex align-items-end"><div class="form-check"><input type="hidden" name="autopsy_done" value="0"><input class="form-check-input" type="checkbox" name="autopsy_done" value="1" id="d-autopsy" @checked(old('autopsy_done'))><label class="form-check-label" for="d-autopsy">{{ __('A post-mortem was done') }}</label></div></div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">{{ __('Save the certificate') }}</button> <a class="btn btn-link" href="{{ route('admin.vital.deaths') }}">{{ __('Cancel') }}</a></div>
    </form>
@stop
