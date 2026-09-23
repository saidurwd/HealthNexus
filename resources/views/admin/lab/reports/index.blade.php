@extends('layouts.adminlte')

@section('page_title', 'Lab Reports')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Lab Reports</h3>
            <div class="card-tools">
                <a href="{{ route('admin.lab.reports.daily-volume') }}" class="btn btn-sm btn-outline-secondary">Daily Volume</a>
                <a href="{{ route('admin.lab.reports.sample-rejection') }}" class="btn btn-sm btn-outline-secondary">Sample Rejection</a>
                <a href="{{ route('admin.lab.reports.critical-results') }}" class="btn btn-sm btn-outline-secondary">Critical Results</a>
                <a href="{{ route('admin.lab.reports.result-amendments') }}" class="btn btn-sm btn-outline-secondary">Amendments</a>
            </div>
        </div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by report number...">
            </form>
            <table class="table table-striped">
                <thead><tr><th>Report #</th><th>Order</th><th>Patient</th><th>Status</th><th>Generated At</th><th></th></tr></thead>
                <tbody>
                    @forelse($reports as $report)
                        <tr>
                            <td>{{ $report->report_number }}</td>
                            <td>{{ $report->labOrder->order_number }}</td>
                            <td>{{ $report->labOrder->patient?->full_name }}</td>
                            <td><span class="badge {{ $report->status === 'amended' ? 'bg-warning' : 'bg-success' }}">{{ ucfirst($report->status) }}</span></td>
                            <td>{{ $report->generated_at?->format('Y-m-d H:i') }}</td>
                            <td><a href="{{ route('admin.lab.reports.show', $report) }}" class="btn btn-xs btn-info">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No reports found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $reports->links() }}</div>
    </div>
@stop
