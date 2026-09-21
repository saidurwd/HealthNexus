@extends('layouts.adminlte')

@section('page_title', 'Queue Management')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Queue Management</h2>
            <button class="btn btn-primary" onclick="callNext()">
                <i class="bi bi-telephone-outgoing me-1"></i>Call Next Patient
            </button>
        </div>

        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Current Token</h5>
                    </div>
                    <div class="card-body text-center" id="current-token">
                        <p class="text-muted">No patient called yet.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Today's Queue</h5>
                        <span class="badge bg-info">{{ $queue->count() }} Patients</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>Token #</th>
                                        <th>Patient</th>
                                        <th>Doctor</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($queue as $item)
                                        <tr id="queue-item-{{ $item->token->id ?? 'token-'.$item->id }}">
                                            <td class="fw-bold">{{ $item->token->token_number ?? $item->appointment_no }}</td>
                                            <td>{{ $item->patient->full_name ?? '-' }}</td>
                                            <td>{{ $item->doctor->name ?? '-' }}</td>
                                            <td>{{ $item->appointment_time->format('g:i A') }}</td>
                                            <td>
                                                @php
    $statusClass = match($item->status) {
        'scheduled' => 'bg-secondary',
        'confirmed' => 'bg-info',
                                                        'checked_in' => 'bg-warning text-dark',
                                                        'in_progress' => 'bg-primary',
                                                        'completed' => 'bg-success',
                                                        default => 'bg-secondary',
                                                    };
                                                @endphp
                                                <span class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if($item->token && $item->token->status === 'waiting')
                                                    <button class="btn btn-sm btn-outline-success" onclick="callToken({{ $item->token->id }})" title="Call">
                                                        <i class="bi bi-telephone"></i>
                                                    </button>
                                                @endif
                                                @if($item->token && $item->token->status === 'called')
                                                    <button class="btn btn-sm btn-outline-primary" onclick="startToken({{ $item->token->id }})" title="Start">
                                                        <i class="bi bi-play"></i>
                                                    </button>
                                                @endif
                                                @if($item->token && $item->token->status === 'in_progress')
                                                    <button class="btn btn-sm btn-outline-success" onclick="completeToken({{ $item->token->id }})" title="Complete">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">No patients in queue.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@section('js')
<script>
function callNext() {
    fetch('{{ route('admin.queue.call-next') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('current-token').innerHTML = `
                <div class="display-4 fw-bold text-danger">${data.token.token_number}</div>
                <div class="text-muted mt-2">${data.token.patient_name}</div>
                <div class="text-muted small">${data.token.appointment_time}</div>
            `;
            location.reload();
        } else {
            alert(data.message || 'No patients in queue.');
        }
    });
}

    function callToken(tokenId) {
        fetch('{{ route('admin.queue.call-next') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ token_id: tokenId }),
        }).then(() => location.reload());
    }

    function startToken(tokenId) {
        fetch('{{ route('admin.queue.start', ['token' => '__TOKEN__']) }}'.replace('__TOKEN__', tokenId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        }).then(() => location.reload());
    }

    function completeToken(tokenId) {
        fetch('{{ route('admin.queue.complete', ['token' => '__TOKEN__']) }}'.replace('__TOKEN__', tokenId), {
</script>
@stop
@endsection
