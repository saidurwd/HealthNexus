@extends('layouts.adminlte')

@section('page_title', 'Provider Calendar')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Provider Calendar</h2>
            <form method="GET" class="d-flex gap-2">
                <select name="provider_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Select Provider</option>
                    @foreach($providers as $provider)
                        <option value="{{ $provider->id }}" {{ (string) $providerId === (string) $provider->id ? 'selected' : '' }}>{{ $provider->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="date" value="{{ $date }}" class="form-control" onchange="this.form.submit()">
            </form>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Token</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($appointments as $appointment)
                            <tr>
                                <td>{{ $appointment->appointment_time->format('H:i') }}</td>
                                <td><a href="{{ route('admin.appointments.show', $appointment) }}">{{ $appointment->patient->full_name ?? '-' }}</a></td>
                                <td>{{ $appointment->token->token_number ?? '-' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">{{ $providerId ? 'No appointments for this provider on this date.' : 'Select a provider to view their calendar.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
