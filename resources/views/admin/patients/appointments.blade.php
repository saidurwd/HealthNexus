@extends('layouts.adminlte')

@section('page_title', $patient->full_name.' - Appointments')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Appointment History</h2>
            <div class="d-flex gap-2">
                @can('create', \App\Models\Appointment::class)
                    <a href="{{ route('admin.appointments.create', ['patient_id' => $patient->id]) }}" class="btn btn-primary">
                        <i class="bi bi-calendar-plus me-1"></i>Book Appointment
                    </a>
                @endcan
                <a href="{{ route('admin.patients.show', $patient) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Profile
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Appointment No.</th>
                                <th>Date</th>
                                <th>Provider</th>
                                <th>Type</th>
                                <th class="text-center">Status</th>
                                <th>Source</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($appointments as $appointment)
                                <tr>
                                    <td><a href="{{ route('admin.appointments.show', $appointment) }}">{{ $appointment->appointment_no }}</a></td>
                                    <td>{{ $appointment->appointment_date->format('M d, Y') }} {{ $appointment->appointment_time->format('H:i') }}</td>
                                    <td>{{ $appointment->provider->name ?? $appointment->doctor->name ?? '-' }}</td>
                                    <td>{{ $appointment->appointmentType->name ?? ucfirst($appointment->type) }}</td>
                                    <td class="text-center"><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</span></td>
                                    <td>{{ ucfirst($appointment->source) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-4 text-muted">No appointments recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($appointments->hasPages())
                <div class="card-footer">{{ $appointments->links() }}</div>
            @endif
        </div>
    </div>
@endsection
