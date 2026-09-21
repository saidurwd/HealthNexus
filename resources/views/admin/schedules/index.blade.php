@extends('layouts.adminlte')

@section('page_title', 'Doctor Schedules')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Doctor Schedules</h2>
            <a href="{{ route('admin.schedules.create') }}" class="btn btn-primary">
                <i class="bi bi-plus me-1"></i>Add Schedule
            </a>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Doctor</th>
                                <th>Name</th>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Slot (min)</th>
                                <th>Department</th>
                                <th class="text-center">Active</th>
                                <th class="text-end">Created</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($schedules as $schedule)
                                <tr>
                                    <td>{{ $schedule->doctor->name ?? '-' }}</td>
                                    <td>{{ $schedule->name }}</td>
                                    <td>{{ ucfirst($schedule->day_of_week) }}</td>
                                    <td>{{ $schedule->start_time->format('H:i') }} - {{ $schedule->end_time->format('H:i') }}</td>
                                    <td>{{ $schedule->slot_duration_minutes }}</td>
                                    <td>{{ $schedule->department->name ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $schedule->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $schedule->is_active ? 'Yes' : 'No' }}</span>
                                    </td>
                                    <td class="text-end"><small class="text-muted">{{ $schedule->created_at->format('M d, Y') }}</small></td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.schedules.show', $schedule) }}" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No schedules found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($schedules->hasPages())
                <div class="card-footer">
                    {{ $schedules->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
