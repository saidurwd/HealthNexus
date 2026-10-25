@extends('layouts.adminlte')

@section('page_title', __('New emergency arrival'))

@section('page_content')
    <form method="POST" action="{{ route('admin.ed.arrivals.store') }}" data-enter-next>
        @csrf
        <div class="card">
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('Known patient (patient ID, optional)') }}</label>
                    <input type="text" name="patient_public_id" class="form-control" value="{{ old('patient_public_id') }}" maxlength="26" placeholder="{{ __('Scan the wristband or leave empty for an unknown patient') }}" autofocus>
                    <div class="form-text">{{ __('Leave empty to register an unknown patient in under a minute. It can be merged into the real record later.') }}</div>
                    @error('patient_public_id') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Arrived by') }}</label>
                    <select name="arrival_mode" class="form-select" required>
                        @foreach($modes as $mode)<option value="{{ $mode }}" @selected(old('arrival_mode') === $mode)>{{ __(\Illuminate\Support\Str::headline($mode)) }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">{{ __('Main problem') }}</label>
                    <input type="text" name="chief_complaint" class="form-control" value="{{ old('chief_complaint') }}" maxlength="500">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Sex (unknown patient)') }}</label>
                    <select name="sex" class="form-select"><option value="">—</option><option value="M">{{ __('Male') }}</option><option value="F">{{ __('Female') }}</option><option value="O">{{ __('Other') }}</option></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Estimated age (years)') }}</label>
                    <input type="number" name="estimated_age" class="form-control" min="0" max="120" value="{{ old('estimated_age') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Description (clothing, belongings, who brought them)') }}</label>
                    <input type="text" name="description" class="form-control" value="{{ old('description') }}" maxlength="500">
                </div>
                <div class="col-12">
                    <div class="form-check"><input type="checkbox" class="form-check-input" name="is_mass_casualty" value="1" id="mci"><label class="form-check-label" for="mci">{{ __('Mass casualty incident') }}</label></div>
                </div>
            </div>
            <div class="card-footer"><button class="btn btn-danger btn-lg" type="submit">{{ __('Register arrival') }}</button> <a href="{{ route('admin.ed.board') }}" class="btn btn-link">{{ __('Back to the board') }}</a></div>
        </div>
    </form>
@stop
