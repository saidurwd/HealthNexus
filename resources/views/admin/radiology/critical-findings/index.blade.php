@extends('layouts.adminlte')

@section('page_title', 'Critical Findings')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Critical Findings</h3>
            <div class="card-tools">
                <a href="{{ route('admin.radiology.critical-findings.index', ['status' => 'unacknowledged']) }}" class="btn btn-sm btn-outline-danger">Unacknowledged only</a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Finding</th><th>Patient</th><th>Detected At</th><th>Notified To</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($findings as $finding)
                        <tr>
                            <td>{{ Str::limit($finding->finding_text, 60) }}</td>
                            <td>{{ $finding->report->examination->orderItem->radiologyOrder->patient?->full_name }}</td>
                            <td>{{ $finding->detected_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $finding->notifiedTo?->name }}</td>
                            <td>
                                @if($finding->isAcknowledged())
                                    <span class="badge bg-success">Acknowledged</span>
                                @else
                                    <span class="badge bg-danger">{{ ucfirst($finding->status) }}</span>
                                @endif
                            </td>
                            <td>
                                @if(! $finding->isAcknowledged())
                                    @can('radiology.critical_finding.acknowledge')
                                        <form method="post" action="{{ route('admin.radiology.critical-findings.acknowledge', $finding) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-xs btn-success">Acknowledge</button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No critical findings.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $findings->links() }}</div>
    </div>
@stop
