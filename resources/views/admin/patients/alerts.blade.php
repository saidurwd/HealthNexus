@extends('layouts.adminlte')

@section('page_title', $patient->full_name.' - Alerts')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Patient Alerts</h2>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAlertModal">
                    <i class="bi bi-plus me-1"></i>Add Alert
                </button>
                <a href="{{ route('admin.patients.show', $patient) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Profile
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Severity</th>
                                <th>Status</th>
                                <th>Start</th>
                                <th>Expiry</th>
                                <th>Created</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($alerts as $alert)
                                <tr>
                                    <td>{{ $alert->alert_type }}</td>
                                    <td><strong>{{ $alert->title }}</strong></td>
                                    <td>{{ $alert->description ? Str::limit($alert->description, 50) : '-' }}</td>
                                    <td>
                                        @if($alert->severity === 'critical')
                                            <span class="badge bg-danger">{{ ucfirst($alert->severity) }}</span>
                                        @elseif($alert->severity === 'warning')
                                            <span class="badge bg-warning text-dark">{{ ucfirst($alert->severity) }}</span>
                                        @else
                                            <span class="badge bg-info">{{ ucfirst($alert->severity) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($alert->isActive())
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($alert->status) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $alert->start_at?->format('Y-m-d') ?? '-' }}</td>
                                    <td>{{ $alert->expires_at?->format('Y-m-d') ?? '-' }}</td>
                                    <td><small class="text-muted">{{ $alert->created_at->format('M d, Y') }}</small></td>
                                    <td class="text-center">
                                        @if($alert->status !== 'resolved')
                                            <form action="{{ route('admin.patients.alerts.resolve', [$patient, $alert]) }}" method="POST" class="d-inline">
                                                @csrf @method('PUT')
                                                <button class="btn btn-sm btn-outline-success" title="Resolve" onclick="return confirm('Mark this alert as resolved?')">
                                                    <i class="bi bi-check"></i> Resolve
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('admin.patients.alerts.delete', [$patient, $alert]) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this alert?')">
                                            @csrf @method('delete')
                                            <button class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No alerts recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addAlertModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.patients.alerts.add', $patient) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Alert</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Type *</label>
                            <input type="text" name="alert_type" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Severity</label>
                            <select name="severity" class="form-select">
                                <option value="info">Info</option>
                                <option value="warning" selected>Warning</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_at" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expires_at" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Alert</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
