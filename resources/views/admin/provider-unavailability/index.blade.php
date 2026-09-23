@extends('layouts.adminlte')

@section('page_title', 'Provider Unavailability / Blocking')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Provider Unavailability / Blocking</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus me-1"></i>Block Time
            </button>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Provider</th>
                                <th>Reason</th>
                                <th>From</th>
                                <th>To</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($entries as $entry)
                                <tr>
                                    <td>{{ $entry->provider->name ?? '-' }}</td>
                                    <td>{{ ucfirst($entry->reason_type) }}</td>
                                    <td>{{ $entry->start_at->format('M d, Y H:i') }}</td>
                                    <td>{{ $entry->end_at->format('M d, Y H:i') }}</td>
                                    <td class="text-center">
                                        <form action="{{ route('admin.provider-unavailability.destroy', $entry) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this block?')">
                                            @csrf
                                            @method('delete')
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-4 text-muted">No blocked/unavailable periods.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($entries->hasPages())
                <div class="card-footer">{{ $entries->links() }}</div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.provider-unavailability.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Block Provider Time</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Provider *</label>
                            <select name="provider_id" class="form-select" required>
                                @foreach($providers as $provider)
                                    <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason *</label>
                            <select name="reason_type" class="form-select" required>
                                @foreach(['leave', 'training', 'meeting', 'conference', 'personal', 'emergency', 'other'] as $reason)
                                    <option value="{{ $reason }}">{{ ucfirst($reason) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">From *</label>
                                <input type="datetime-local" name="start_at" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">To *</label>
                                <input type="datetime-local" name="end_at" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-0 mt-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
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
@endsection
