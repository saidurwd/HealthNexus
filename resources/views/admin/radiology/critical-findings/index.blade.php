@extends('layouts.adminlte')

@section('page_title', 'Critical Findings')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Critical Findings') }}</h3>
            <div class="card-tools">
                <a href="{{ route('admin.radiology.critical-findings.index', ['status' => 'unacknowledged']) }}" class="btn btn-sm btn-outline-danger">{{ __('Unacknowledged only') }}</a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>{{ __('Finding') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Detected At') }}</th><th>{{ __('Notified To') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($findings as $finding)
                        <tr>
                            <td>{{ Str::limit($finding->finding_text, 60) }}</td>
                            <td>{{ $finding->report->examination->orderItem->radiologyOrder->patient?->full_name }}</td>
                            <td>{{ $finding->detected_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $finding->notifiedTo?->name }}</td>
                            <td>
                                @if($finding->isAcknowledged())
                                    <span class="badge bg-success">{{ __('Acknowledged') }}</span>
                                @else
                                    <x-ui.status-badge :status="$finding->status" />
                                @endif
                            </td>
                            <td>
                                @if(! $finding->isAcknowledged())
                                    @can('radiology.critical_finding.acknowledge')
                                        <form method="post" action="{{ route('admin.radiology.critical-findings.acknowledge', $finding) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-xs btn-success">{{ __('Acknowledge') }}</button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">{{ __('No critical findings.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $findings->links() }}</div>
    </div>
@stop
