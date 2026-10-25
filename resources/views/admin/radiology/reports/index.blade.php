@extends('layouts.adminlte')

@section('page_title', 'Radiology Reports')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Radiology Reports') }}</h3>
            <div class="card-tools">
                <a href="{{ route('admin.radiology.analytics.daily-volume') }}" class="btn btn-sm btn-outline-secondary">{{ __('Daily Volume') }}</a>
                <a href="{{ route('admin.radiology.analytics.modality-utilization') }}" class="btn btn-sm btn-outline-secondary">{{ __('Modality Utilization') }}</a>
                <a href="{{ route('admin.radiology.analytics.turnaround-time') }}" class="btn btn-sm btn-outline-secondary">{{ __('Turnaround Time') }}</a>
            </div>
        </div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('Search by report number...') }}">
            </form>
            <table class="table table-striped">
                <thead><tr><th>{{ __('Report #') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Procedure') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($reports as $report)
                        <tr>
                            <td>{{ $report->report_number }}</td>
                            <td>{{ $report->examination->orderItem->radiologyOrder->patient?->full_name }}</td>
                            <td>{{ $report->examination->orderItem->procedure?->name }}</td>
                            <td><x-ui.status-badge :status="$report->status" /></td>
                            <td><a href="{{ route('admin.radiology.reports.show', $report) }}" class="btn btn-xs btn-info">{{ __('View') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">{{ __('No reports found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $reports->links() }}</div>
    </div>
@stop
