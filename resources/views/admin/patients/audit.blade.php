@extends('layouts.adminlte')

@section('page_title', $patient->full_name.' - Audit Trail')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Audit Trail</h2>
            <a href="{{ route('admin.patients.show', $patient) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Profile
            </a>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Action</th>
                                <th>User</th>
                                <th>When</th>
                                <th>Request</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td><span class="badge bg-secondary">{{ $log->action }}</span></td>
                                    <td>{{ $log->user?->name ?? 'System' }}</td>
                                    <td><small class="text-muted">{{ $log->created_at->format('M d, Y H:i') }}</small></td>
                                    <td><small class="text-muted">{{ $log->request_id ?? '-' }}</small></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No audit entries recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
@endsection
