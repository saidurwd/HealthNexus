@extends('layouts.adminlte')

@section('page_title', 'New Surgical Case')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('New Surgical Case') }}</h3></div>
        <form action="{{ route('admin.ot.surgeries.store') }}" method="post">
            @csrf
            <div class="card-body row">
                @if($surgeryRequest)
                    <input type="hidden" name="surgery_request_id" value="{{ $surgeryRequest->id }}">
                    <div class="col-md-12 mb-3">{{ __('From approved request for') }} <strong>{{ $surgeryRequest->patient?->full_name }}</strong> — {{ $surgeryRequest->procedure?->name }}</div>
                @else
                    <div class="col-md-4 mb-3"><label>{{ __('Patient ID') }}</label><input type="number" name="patient_id" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label>{{ __('Encounter ID') }}</label><input type="number" name="encounter_id" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label>{{ __('Admission ID (optional)') }}</label><input type="number" name="admission_id" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label>{{ __('Primary Procedure ID') }}</label><input type="number" name="primary_procedure_id" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label>{{ __('Specialty ID') }}</label><input type="number" name="specialty_id" class="form-control"></div>
                    <div class="col-md-4 mb-3">
                        <label>{{ __('Surgery Type') }}</label>
                        <select name="surgery_type" class="form-control">
                            <option value="elective">{{ __('Elective') }}</option>
                            <option value="emergency">{{ __('Emergency') }}</option>
                        </select>
                    </div>
                @endif
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.ot.surgeries.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button class="btn btn-primary">{{ __('Create') }}</button>
            </div>
        </form>
    </div>
@stop
