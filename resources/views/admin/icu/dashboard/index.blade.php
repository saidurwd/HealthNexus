@extends('layouts.adminlte')

@section('page_title', 'ICU Dashboard')

@section('page_content')
    <div class="row">
        @foreach($metrics as $label => $value)
            <div class="col-md-3 col-sm-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h3>{{ $value }}</h3>
                        <div class="text-muted">{{ ucwords(str_replace('_', ' ', $label)) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Unacknowledged Critical Alerts</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Patient</th><th>Rule</th><th>Severity</th><th>Triggered</th><th></th></tr></thead>
                <tbody>
                    @forelse($criticalAlerts as $alert)
                        <tr>
                            <td>{{ $alert->episode->patient?->full_name }}</td>
                            <td>{{ $alert->rule }}</td>
                            <td><span class="badge bg-danger">{{ $alert->severity }}</span></td>
                            <td>{{ $alert->triggered_at->diffForHumans() }}</td>
                            <td><a href="{{ route('admin.icu.alerts.index') }}" class="btn btn-xs btn-info">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No unacknowledged alerts.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
