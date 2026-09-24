@extends('layouts.adminlte')

@section('page_title', 'New Admission Request')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">New Admission Request</h3></div>
        <form action="{{ route('admin.ipd.admission-requests.store') }}" method="post">
            @csrf
            <div class="card-body row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Patient</label>
                        <input type="number" name="patient_id" value="{{ old('patient_id', $patient?->id) }}" class="form-control @error('patient_id') is-invalid @enderror" placeholder="Patient ID" required>
                        @if($patient)<div class="form-text">{{ $patient->full_name }} ({{ $patient->enterprise_patient_no }})</div>@endif
                        @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Admission Type</label>
                        <select name="admission_type_id" class="form-control">
                            <option value="">-</option>
                            @foreach($admissionTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Admission Source</label>
                        <select name="admission_source_id" class="form-control">
                            <option value="">-</option>
                            @foreach($admissionSources as $source)
                                <option value="{{ $source->id }}">{{ $source->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-control" required>
                            <option value="routine">Routine</option>
                            <option value="urgent">Urgent</option>
                            <option value="emergency">Emergency</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Reason for Admission</label>
                        <textarea name="reason" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Provisional Diagnosis</label>
                        <textarea name="provisional_diagnosis" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Expected Admission Date</label>
                        <input type="date" name="expected_admission_date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Expected Discharge Date</label>
                        <input type="date" name="expected_discharge_date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Required Bed Type</label>
                        <select name="required_bed_type_id" class="form-control">
                            <option value="">-</option>
                            @foreach($bedTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Isolation Requirement</label>
                        <input type="text" name="isolation_requirement" class="form-control" placeholder="e.g. contact, droplet, airborne">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Special Requirements</label>
                        <textarea name="special_requirements" class="form-control"></textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.ipd.admission-requests.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
@stop
