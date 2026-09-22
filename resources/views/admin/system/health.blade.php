@extends('layouts.adminlte')

@section('page_title', 'System Health')

@section('page_content')
<div class="card">
    <div class="card-header"><h3 class="card-title">System Health</h3></div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Component</th>
                    <th>Status</th>
                    <th>Details</th>
                    <th class="text-end">Latency</th>
                </tr>
            </thead>
            <tbody>
                @foreach($checks as $component => $check)
                    <tr>
                        <td>{{ ucfirst($component) }}</td>
                        <td>
                            @php
                                $badge = match($check['status']) {
                                    'healthy' => 'bg-success',
                                    'degraded' => 'bg-warning',
                                    default => 'bg-danger',
                                };
                            @endphp
                            <span class="badge {{ $badge }}">
                                <i class="bi bi-circle-fill"></i> {{ ucfirst($check['status']) }}
                            </span>
                        </td>
                        <td>{{ $check['message'] }}</td>
                        <td class="text-end">{{ $check['latency_ms'] !== null ? $check['latency_ms'].' ms' : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted small">
        Checked at {{ now()->format('Y-m-d H:i:s') }}. Reload the page to re-run all checks.
    </div>
</div>
@stop
