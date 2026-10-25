@extends('layouts.adminlte')

@section('page_title', 'Shift Handover')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Handovers') }}</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>{{ __('Patient') }}</th><th>{{ __('Outgoing') }}</th><th>{{ __('Incoming') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($handovers as $h)
                        <tr>
                            <td>{{ $h->episode?->patient?->full_name }}</td>
                            <td>{{ $h->outgoingNurse?->name }}</td>
                            <td>{{ $h->incomingNurse?->name ?? '—' }}</td>
                            <td><x-ui.status-badge :status="$h->status" /></td>
                            <td><a href="{{ route('admin.nursing.handover.show', $h) }}" class="btn btn-xs btn-info">{{ __('Open') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">{{ __('No handovers found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $handovers->links() }}</div>
    </div>
@stop
