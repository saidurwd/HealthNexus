@extends('layouts.adminlte')

@section('page_title', 'Appointments')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h2 class="mb-0">Appointments</h2>
            <a href="{{ route('admin.appointments.create') }}" class="btn btn-primary">
                <i class="bi bi-plus me-1"></i>Book Appointment
            </a>
        </div>

        <form method="GET" class="mb-3">
            <div class="row g-2">
                <div class="col-md-3">
                    <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                </div>
                <div class="col-md-3">
                    <select name="doctor_id" class="form-select">
                        <option value="">All Doctors</option>
                        @foreach($doctors ?? [] as $doctor)
                            <option value="{{ $doctor->id }}" {{ request('doctor_id') == $doctor->id ? 'selected' : '' }}>{{ $doctor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="checked_in" {{ request('status') == 'checked_in' ? 'selected' : '' }}>Checked In</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Date & Time</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Token</th>
                                <th class="text-end">Created</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($appointments as $appointment)
                                <tr>
                                    <td><a href="{{ route('admin.patients.show', $appointment->patient) }}" class="text-decoration-none fw-medium">{{ $appointment->patient->full_name }}</a></td>
                                    <td>{{ $appointment->doctor->name ?? '-' }}</td>
                                    <td>{{ $appointment->appointment_date->format('M d, Y') }} {{ $appointment->appointment_time->format('g:i A') }}</td>
                                    <td>{{ ucfirst($appointment->type) }}</td>
                                    <td>
                                        @php
                                            $statusClass = match($appointment->status) {
                                                'scheduled' => 'bg-secondary',
                                                'confirmed' => 'bg-info',
                                                'checked_in' => 'bg-warning text-dark',
                                                'in_progress' => 'bg-primary',
                                                'completed' => 'bg-success',
                                                'cancelled' => 'bg-danger',
                                                'no_show' => 'bg-dark',
                                                default => 'bg-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</span>
                                    </td>
                                    <td>{{ $appointment->token->token_number ?? '-' }}</td>
                                    <td class="text-end"><small class="text-muted">{{ $appointment->created_at->format('M d, Y') }}</small></td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.appointments.show', $appointment) }}" class="btn btn-sm btn-outline-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No appointments found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($appointments->hasPages())
                <div class="card-footer">
                    {{ $appointments->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
