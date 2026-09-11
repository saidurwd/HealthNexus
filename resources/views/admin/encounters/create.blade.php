@extends('adminlte::page')

@section('title', 'Create Encounter')

@section('content_header')
    <h1>Create Encounter</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.encounters.store') }}">
                @csrf
                <div class="form-group">
                    <label>Company</label>
                    <select name="company_id" class="form-control" required>
                        <option value="">Select Company</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('company_id') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label>Branch</label>
                    <select name="branch_id" class="form-control" required>
                        <option value="">Select Branch</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('branch_id') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label>Patient</label>
                    <select name="patient_id" class="form-control" required>
                        <option value="">Select Patient</option>
                        @foreach ($patients as $patient)
                            <option value="{{ $patient->id }}" {{ old('patient_id') == $patient->id ? 'selected' : '' }}>
                                {{ $patient->full_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('patient_id') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label>Encounter Type</label>
                    <select name="encounter_type" class="form-control" required>
                        <option value="">Select Type</option>
                        @foreach (['OPD', 'IPD', 'ER', 'LAB', 'RADIOLOGY', 'FOLLOW_UP', 'TELEMEDICINE'] as $type)
                            <option value="{{ $type }}" {{ old('encounter_type') == $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                    @error('encounter_type') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="btn btn-primary">Create Encounter</button>
            </form>
        </div>
    </div>
@stop
