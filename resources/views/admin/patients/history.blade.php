@extends('layouts.adminlte')

@section('page_title', $patient->full_name.' - Medical History')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Medical History</h3>
            <div class="card-tools">
                <a href="{{ route('admin.patients.show', $patient) }}" class="btn btn-secondary btn-sm">Back to Profile</a>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHistoryModal">
                    Add Medical History
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Condition</th>
                            <th>Description</th>
                            <th>Diagnosed</th>
                            <th>Resolved</th>
                            <th>Status</th>
                            <th>Recorded By</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($histories as $history)
                            <tr>
                                <td>{{ $history->condition }}</td>
                                <td>{{ $history->description ?? '-' }}</td>
                                <td>{{ $history->diagnosed_at?->format('Y-m-d') ?? '-' }}</td>
                                <td>{{ $history->resolved_at?->format('Y-m-d') ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $history->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $history->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>{{ $history->recordedBy->name ?? '-' }}</td>
                                <td>{{ $history->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <form action="{{ route('admin.patients.history.delete', [$patient, $history]) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                        @csrf
                                        @method('delete')
                                        <button class="btn btn-xs btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No medical history found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Add History Modal --}}
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
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Diagnosed At</label>
                            <input type="date" name="diagnosed_at" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Resolved At</label>
                            <input type="date" name="resolved_at" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop
