@extends('layouts.adminlte')

@section('page_title', $patient->full_name.' - Medical History')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Medical History</h2>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHistoryModal">
                    <i class="bi bi-plus me-1"></i>Add History
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
                                <th>Condition</th>
                                <th>Description</th>
                                <th class="text-end">Diagnosed</th>
                                <th class="text-end">Resolved</th>
                                <th class="text-center">Status</th>
                                <th>Recorded By</th>
                                <th class="text-end">Created</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($histories as $history)
                                <tr>
                                    <td>
                                        <strong>{{ $history->condition }}</strong>
                                    </td>
                                    <td>{{ $history->description ?? '-' }}</td>
                                    <td class="text-end">{{ $history->diagnosed_at?->format('M d, Y') ?? '-' }}</td>
                                    <td class="text-end">{{ $history->resolved_at?->format('M d, Y') ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($history->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ $history->recordedBy->name ?? '-' }}</td>
                                    <td class="text-end"><small class="text-muted">{{ $history->created_at->format('M d, Y') }}</small></td>
                                    <td class="text-center">
                                        <form action="{{ route('admin.patients.history.delete', [$patient, $history]) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this history record?')">
                                            @csrf
                                            @method('delete')
                                            <button class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No medical history found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addHistoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.patients.history.add', $patient) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Medical History</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Condition *</label>
                            <input type="text" name="condition" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Additional details..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Diagnosed At</label>
                            <input type="date" name="diagnosed_at" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Resolved At</label>
                            <input type="date" name="resolved_at" class="form-control">
                        </div>
                        <div class="mb-0 form-check">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" checked>
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save History</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
