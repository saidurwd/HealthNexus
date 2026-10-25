@extends('layouts.adminlte')

@section('page_title', 'Book Appointment')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">{{ __('Book New Appointment') }}</h2>
            <a href="{{ route('admin.appointments.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back') }}
            </a>
        </div>

        <form method="POST" action="{{ route('admin.appointments.store') }}">
            @csrf
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">{{ __('Appointment Details') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">{{ __('Patient *') }}</label>
                                <select name="patient_id" class="form-select @error('patient_id') is-invalid @enderror" required>
                                    <option value="">{{ __('Select Patient') }}</option>
                                    @if($selectedPatient ?? null)
                                        <option value="{{ $selectedPatient->id }}" selected>{{ $selectedPatient->full_name }} ({{ $selectedPatient->enterprise_patient_no ?? 'N/A' }})</option>
                                    @endif
                                    @foreach($patients as $patient)
                                        @continue(($selectedPatient ?? null) && $patient->id === $selectedPatient->id)
                                        <option value="{{ $patient->id }}" {{ old('patient_id') == $patient->id ? 'selected' : '' }}>{{ $patient->full_name }} ({{ $patient->enterprise_patient_no ?? 'N/A' }})</option>
                                    @endforeach
                                </select>
                                @if($selectedPatient ?? null)
                                    <div class="form-text">{{ __('Pre-selected from the patient\'s profile — change it explicitly if this is the wrong patient.') }}</div>
                                @endif
                                @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Doctor *') }}</label>
                                <select name="doctor_id" class="form-select @error('doctor_id') is-invalid @enderror" required>
                                    <option value="">{{ __('Select Doctor') }}</option>
                                    @foreach($doctors as $doctor)
                                        <option value="{{ $doctor->id }}" {{ old('doctor_id') == $doctor->id ? 'selected' : '' }}>{{ $doctor->name }}</option>
                                    @endforeach
                                </select>
                                @error('doctor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Appointment Date *') }}</label>
                                <input type="date" name="appointment_date" class="form-control @error('appointment_date') is-invalid @enderror" value="{{ old('appointment_date') }}" required>
                                @error('appointment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Appointment Time *') }}</label>
                                <input type="time" name="appointment_time" class="form-control @error('appointment_time') is-invalid @enderror" value="{{ old('appointment_time') }}" required>
                                @error('appointment_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Type') }}</label>
                                <select name="type" class="form-select">
                                    <option value="scheduled">{{ __('Scheduled') }}</option>
                                    <option value="walk_in">{{ __('Walk-in') }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Source') }}</label>
                                <select name="source" class="form-select">
                                    <option value="online">{{ __('Online') }}</option>
                                    <option value="offline">{{ __('Offline') }}</option>
                                    <option value="referral">{{ __('Referral') }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Reason') }}</label>
                                <textarea name="reason" class="form-control" rows="2" placeholder="Chief complaint or reason for visit..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">{{ __('Actions') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-save me-1"></i>{{ __('Save Appointment') }}
                                </button>
                                <a href="{{ route('admin.appointments.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-x me-1"></i>{{ __('Cancel') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
