@extends('layouts.adminlte')

@section('page_title', 'Critical Results')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Critical Results') }}</h3>
            <div class="card-tools">
                <a href="{{ route('admin.lab.critical-results.index', ['status' => 'unacknowledged']) }}" class="btn btn-sm btn-outline-danger">{{ __('Unacknowledged only') }}</a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>{{ __('Test') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Value') }}</th><th>{{ __('Detected At') }}</th><th>{{ __('Notified To') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
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
                                    <span class="badge bg-success">{{ __('Acknowledged') }}</span>
                                @else
                                    <span class="badge bg-danger">{{ __('Pending') }}</span>
                                @endif
                            </td>
                            <td>
                                @if(! $alert->isAcknowledged())
                                    @can('lab.critical_result.acknowledge')
                                        <form method="post" action="{{ route('admin.lab.critical-results.acknowledge', $alert) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-xs btn-success">{{ __('Acknowledge') }}</button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">{{ __('No critical results.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $alerts->links() }}</div>
    </div>
@stop
