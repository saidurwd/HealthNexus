@extends('layouts.adminlte')

@section('page_title', 'Radiology Reports')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Radiology Reports</h3>
            <div class="card-tools">
                <a href="{{ route('admin.radiology.analytics.daily-volume') }}" class="btn btn-sm btn-outline-secondary">Daily Volume</a>
                <a href="{{ route('admin.radiology.analytics.modality-utilization') }}" class="btn btn-sm btn-outline-secondary">Modality Utilization</a>
                <a href="{{ route('admin.radiology.analytics.turnaround-time') }}" class="btn btn-sm btn-outline-secondary">Turnaround Time</a>
            </div>
        </div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by report number...">
            </form>
            <table class="table table-striped">
                <thead><tr><th>Report #</th><th>Patient</th><th>Procedure</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($reports as $report)
                        <tr>
                            <td>{{ $report->report_number }}</td>
                            <td>{{ $report->examination->orderItem->radiologyOrder->patient?->full_name }}</td>
                            <td>{{ $report->examination->orderItem->procedure?->name }}</td>
                            <td><span class="badge {{ $report->status === 'amended' ? 'bg-warning' : ($report->isFinal() ? 'bg-success' : 'bg-secondary') }}">{{ ucfirst($report->status) }}</span></td>
                            <td><a href="{{ route('admin.radiology.reports.show', $report) }}" class="btn btn-xs btn-info">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No reports found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $reports->links() }}</div>
    </div>
@stop
