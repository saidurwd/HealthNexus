@extends('layouts.adminlte')

@section('page_title', 'Appointment Slip')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
            <h2 class="mb-0">Appointment Slip</h2>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
                <a href="{{ route('admin.appointments.show', $appointment) }}" class="btn btn-outline-secondary">Back</a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4>Appointment #{{ $appointment->appointment_no }}</h4>
                <div class="row g-3 mt-2">
                    <div class="col-md-6"><strong>Patient:</strong> {{ $appointment->patient->full_name ?? '-' }}</div>
                    <div class="col-md-6"><strong>MRN:</strong> {{ $appointment->patient->enterprise_patient_no ?? '-' }}</div>
                    <div class="col-md-6"><strong>Provider:</strong> {{ $appointment->provider->name ?? $appointment->doctor->name ?? '-' }}</div>
                    <div class="col-md-6"><strong>Type:</strong> {{ $appointment->appointmentType->name ?? ucfirst($appointment->type) }}</div>
                    <div class="col-md-6"><strong>Date:</strong> {{ $appointment->appointment_date->format('M d, Y') }}</div>
                    <div class="col-md-6"><strong>Time:</strong> {{ $appointment->appointment_time->format('H:i') }}</div>
                    <div class="col-md-6"><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</div>
                    <div class="col-md-6"><strong>Reason:</strong> {{ $appointment->reason ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
