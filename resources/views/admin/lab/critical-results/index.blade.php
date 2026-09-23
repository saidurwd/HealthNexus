@extends('layouts.adminlte')

@section('page_title', 'Critical Results')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Critical Results</h3>
            <div class="card-tools">
                <a href="{{ route('admin.lab.critical-results.index', ['status' => 'unacknowledged']) }}" class="btn btn-sm btn-outline-danger">Unacknowledged only</a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Test</th><th>Patient</th><th>Value</th><th>Detected At</th><th>Notified To</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($alerts as $alert)
                        <tr>
                            <td>{{ $alert->result->test->name }}</td>
                            <td>{{ $alert->result->orderItem->labOrder->patient?->full_name }}</td>
                            <td>{{ $alert->result->numeric_value ?? $alert->result->qualitative_value ?? $alert->result->text_value }} {{ $alert->result->unit }}</td>
                            <td>{{ $alert->detected_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $alert->notifiedTo?->name }}</td>
                            <td>
                                @if($alert->isAcknowledged())
                                    <span class="badge bg-success">Acknowledged</span>
                                @else
                                    <span class="badge bg-danger">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if(! $alert->isAcknowledged())
                                    @can('lab.critical_result.acknowledge')
                                        <form method="post" action="{{ route('admin.lab.critical-results.acknowledge', $alert) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-xs btn-success">Acknowledge</button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No critical results.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $alerts->links() }}</div>
    </div>
@stop
