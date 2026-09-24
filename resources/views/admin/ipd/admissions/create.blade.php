@extends('layouts.adminlte')

@section('page_title', 'Admit Patient')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Admit Patient</h3></div>
        <form action="{{ route('admin.ipd.admissions.store') }}" method="post">
            @csrf
            <input type="hidden" name="admission_request_id" value="{{ $admissionRequest?->id }}">
            <div class="card-body row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Patient</label>
                        <input type="number" name="patient_id" value="{{ old('patient_id', $admissionRequest?->patient_id) }}" class="form-control @error('patient_id') is-invalid @enderror" placeholder="Patient ID" required>
                        @if($admissionRequest?->patient)<div class="form-text">{{ $admissionRequest->patient->full_name }}</div>@endif
                        @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Admitting Provider ID</label>
                        <input type="number" name="admitting_provider_id" class="form-control" placeholder="Provider ID">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Attending Provider ID</label>
                        <input type="number" name="attending_provider_id" class="form-control" placeholder="Provider ID">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-control">
                            <option value="routine">Routine</option>
                            <option value="urgent">Urgent</option>
                            <option value="emergency">Emergency</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Expected Discharge Date</label>
                        <input type="date" name="expected_discharge_date" value="{{ old('expected_discharge_date', $admissionRequest?->expected_discharge_date?->toDateString()) }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bed (optional — allocate now)</label>
                        <select name="bed_id" class="form-control">
                            <option value="">No bed yet</option>
                            @foreach($availableBeds as $bed)
                                <option value="{{ $bed->id }}">{{ $bed->room?->ward?->name }} / {{ $bed->room?->room_number }} / {{ $bed->bed_code }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.ipd.admissions.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Admit Patient</button>
            </div>
        </form>
    </div>
@stop
