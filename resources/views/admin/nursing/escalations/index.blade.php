@extends('layouts.adminlte')

@section('page_title', 'Escalations')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Escalations</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Patient</th><th>Concern</th><th>Severity</th><th>State</th><th></th></tr></thead>
                <tbody>
                    @forelse($escalations as $e)
                        <tr>
                            <td>{{ $e->patient?->full_name }}</td>
                            <td>{{ $e->concern }}</td>
                            <td>{{ ucfirst($e->severity) }}</td>
                            <td>{{ $e->resolved_at ? 'Resolved' : ($e->acknowledged_at ? 'Acknowledged' : 'Open') }}</td>
                            <td>
                                @if(! $e->acknowledged_at)
                                    @can('nursing.escalation.acknowledge')
                                        <form action="{{ route('admin.nursing.escalations.acknowledge', $e) }}" method="post" class="d-inline">@csrf<button class="btn btn-xs btn-info">Acknowledge</button></form>
                                    @endcan
                                @endif
                                @if(! $e->resolved_at)
                                    @can('nursing.escalation.resolve')
                                        <form action="{{ route('admin.nursing.escalations.resolve', $e) }}" method="post" class="d-inline">
                                            @csrf<input name="action_taken" placeholder="Action taken" required><button class="btn btn-xs btn-success">Resolve</button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No escalations found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $escalations->links() }}</div>
    </div>
@stop
