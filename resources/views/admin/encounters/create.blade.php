@extends('layouts.adminlte')

@section('page_title', 'Create Encounter')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Create Encounter</h3>
            <p class="text-muted mb-0">Most encounters are created automatically from the OPD "Start Consultation" action. Use this form only for a walk-in or manual encounter that isn't tied to an appointment.</p>
        </div>
        <form method="POST" action="{{ route('admin.encounters.store') }}">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label for="patient_id" class="form-label">Patient *</label>
                    <select name="patient_id" id="patient_id" class="form-control @error('patient_id') is-invalid @enderror" required>
                        <option value="">Select Patient</option>
                        @foreach ($patients as $patient)
                            <option value="{{ $patient->id }}" {{ old('patient_id') == $patient->id ? 'selected' : '' }}>{{ $patient->full_name }} ({{ $patient->enterprise_patient_no ?? 'N/A' }})</option>
                        @endforeach
                    </select>
                    @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="encounter_type_id" class="form-label">Encounter Type</label>
                    <select name="encounter_type_id" id="encounter_type_id" class="form-control @error('encounter_type_id') is-invalid @enderror">
                        <option value="">Select Type</option>
                        @foreach (\App\Models\EncounterType::where('company_id', app(\App\Services\TenantContextResolver::class)->getCompanyId())->where('is_active', true)->get() as $type)
                            <option value="{{ $type->id }}" {{ old('encounter_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                        @endforeach
                    </select>
                    @error('encounter_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="priority" class="form-label">Priority</label>
                    <select name="priority" id="priority" class="form-control">
                        <option value="routine" selected>Routine</option>
                        <option value="urgent">Urgent</option>
                        <option value="stat">Stat</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="reason_for_visit" class="form-label">Reason for Visit</label>
                    <textarea name="reason_for_visit" id="reason_for_visit" class="form-control" rows="2">{{ old('reason_for_visit') }}</textarea>
                </div>
                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea name="notes" id="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.encounters.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Encounter</button>
            </div>
        </form>
    </div>
@stop
