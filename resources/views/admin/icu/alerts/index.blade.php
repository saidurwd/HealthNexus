@extends('layouts.adminlte')

@section('page_title', 'ICU Critical Alerts')

@section('page_content')
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Patient</th><th>Rule</th><th>Severity</th><th>Triggered</th><th>Acknowledged</th><th></th></tr></thead>
                <tbody>
                    @forelse($alerts as $alert)
                        <tr>
                            <td>{{ $alert->episode->patient?->full_name }}</td>
                            <td>{{ $alert->rule }}</td>
                            <td><span class="badge bg-danger">{{ $alert->severity }}</span></td>
                            <td>{{ $alert->triggered_at->diffForHumans() }}</td>
                            <td>{{ $alert->acknowledged_at ? $alert->acknowledged_at->diffForHumans() : '—' }}</td>
                            <td>
                                @unless($alert->acknowledged_at)
                                    <form method="POST" action="{{ route('admin.icu.alerts.acknowledge', $alert) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-xs btn-success">Acknowledge</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No critical alerts.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $alerts->links() }}
@stop
