@extends('layouts.adminlte')

@section('page_title', 'Pending Amendments')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Pending Patient Amendments</h2>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Patient</th>
                                <th>Proposed Changes</th>
                                <th>Reason</th>
                                <th>Requested By</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($amendments as $amendment)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.patients.show', $amendment->patient) }}">{{ $amendment->patient?->full_name }}</a>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            @foreach($amendment->proposed_changes as $field => $value)
                                                {{ $field }}: <strong>{{ $value }}</strong><br>
                                            @endforeach
                                        </small>
                                    </td>
                                    <td>{{ $amendment->reason }}</td>
                                    <td>{{ $amendment->requestedBy?->name ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark">{{ str_replace('_', ' ', ucfirst($amendment->status)) }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($amendment->status === 'pending_approval')
                                            <form action="{{ route('admin.patients.amendments.approve', $amendment) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('put')
                                                <button class="btn btn-sm btn-outline-success">Approve</button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $amendment->id }}">Reject</button>

                                            <div class="modal fade" id="rejectModal{{ $amendment->id }}" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form action="{{ route('admin.patients.amendments.reject', $amendment) }}" method="POST">
                                                            @csrf
                                                            @method('put')
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Reject Amendment</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <label class="form-label">Reason *</label>
                                                                <textarea name="reason" class="form-control" required></textarea>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-danger">Reject</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No amendments pending review.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                {{ $amendments->links() }}
            </div>
        </div>
    </div>
@endsection
