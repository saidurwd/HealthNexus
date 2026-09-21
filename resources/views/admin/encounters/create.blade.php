@extends('layouts.adminlte')

@section('page_title', 'Create Encounter')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Create Encounter</h3>
        </div>
        <form method="POST" action="{{ route('admin.encounters.store') }}">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label for="company_id" class="form-label">Company</label>
                    <select name="company_id" id="company_id" class="form-control @error('company_id') is-invalid @enderror" required>
                        <option value="">Select Company</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="branch_id" class="form-label">Branch</label>
                    <select name="branch_id" id="branch_id" class="form-control @error('branch_id') is-invalid @enderror" required>
                        <option value="">Select Branch</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="patient_id" class="form-label">Patient</label>
                    <select name="patient_id" id="patient_id" class="form-control @error('patient_id') is-invalid @enderror" required>
                        <option value="">Select Patient</option>
                        @foreach ($patients as $patient)
                            <option value="{{ $patient->id }}" {{ old('patient_id') == $patient->id ? 'selected' : '' }}>
                                {{ $patient->full_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="encounter_type" class="form-label">Encounter Type</label>
                    <select name="encounter_type" id="encounter_type" class="form-control @error('encounter_type') is-invalid @enderror" required>
                        <option value="">Select Type</option>
                        @foreach (['OPD', 'IPD', 'ER', 'LAB', 'RADIOLOGY', 'FOLLOW_UP', 'TELEMEDICINE'] as $type)
                            <option value="{{ $type }}" {{ old('encounter_type') == $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                    @error('encounter_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.encounters.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Encounter</button>
            </div>
        </form>
    </div>
@stop
