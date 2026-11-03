@extends('layouts.adminlte')

@section('page_title', __('Register a police case'))

@section('page_content')
    @include('admin.vital._flash')
    <form method="POST" action="{{ route('admin.vital.medicolegal.store') }}" class="card">@csrf
        <div class="card-body row g-3">
            <div class="col-md-6"><label class="form-label" for="m-patient">{{ __('Patient') }}</label><x-ui.patient-select name="patient_id" id="m-patient" required /></div>
            <div class="col-md-3"><label class="form-label" for="m-kind">{{ __('Kind of case') }}</label><select id="m-kind" name="kind" class="form-select" required>@foreach(config('vital.medicolegal_kinds') as $x)<option value="{{ $x }}" @selected(old('kind') === $x)>{{ __(\Illuminate\Support\Str::headline($x)) }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label" for="m-brought">{{ __('Brought to the hospital at') }}</label><input id="m-brought" type="datetime-local" name="brought_at" value="{{ old('brought_at', now('Asia/Dhaka')->format('Y-m-d\TH:i')) }}" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label" for="m-occurred">{{ __('It happened at (if known)') }}</label><input id="m-occurred" type="datetime-local" name="occurred_at" value="{{ old('occurred_at') }}" class="form-control"></div>
            <div class="col-md-9"><label class="form-label" for="m-by">{{ __('Brought by') }}</label><input id="m-by" name="brought_by" value="{{ old('brought_by') }}" class="form-control" maxlength="150"></div>
            <div class="col-12"><label class="form-label" for="m-desc">{{ __('What happened') }}</label><textarea id="m-desc" name="description" rows="3" class="form-control" maxlength="3000" required>{{ old('description') }}</textarea></div>
            <div class="col-12"><label class="form-label" for="m-inj">{{ __('Injuries found') }}</label><textarea id="m-inj" name="injuries" rows="3" class="form-control" maxlength="3000">{{ old('injuries') }}</textarea></div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">{{ __('Register the case') }}</button> <a class="btn btn-link" href="{{ route('admin.vital.medicolegal') }}">{{ __('Cancel') }}</a></div>
    </form>
@stop
