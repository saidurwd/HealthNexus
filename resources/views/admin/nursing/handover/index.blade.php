@extends('layouts.adminlte')

@section('page_title', 'Shift Handover')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Handovers</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Patient</th><th>Outgoing</th><th>Incoming</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($handovers as $h)
                        <tr>
                            <td>{{ $h->episode?->patient?->full_name }}</td>
                            <td>{{ $h->outgoingNurse?->name }}</td>
                            <td>{{ $h->incomingNurse?->name ?? '—' }}</td>
                            <td><span class="badge bg-info">{{ ucfirst(str_replace('_',' ',$h->status)) }}</span></td>
                            <td><a href="{{ route('admin.nursing.handover.show', $h) }}" class="btn btn-xs btn-info">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No handovers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $handovers->links() }}</div>
    </div>
@stop
