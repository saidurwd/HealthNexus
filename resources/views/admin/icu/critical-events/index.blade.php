@extends('layouts.adminlte')

@section('page_title', 'Code Blue / Critical Events')

@section('page_content')
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Patient</th><th>Type</th><th>Time</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($events as $event)
                        <tr>
                            <td>{{ $event->episode->patient?->full_name }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $event->event_type)) }}</td>
                            <td>{{ $event->event_time->format('Y-m-d H:i') }}</td>
                            <td><span class="badge bg-info">{{ $event->status }}</span></td>
                            <td>
                                @if($event->status === 'draft')
                                    <form method="POST" action="{{ route('admin.icu.critical-events.finalize', $event) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-xs btn-success">Finalize</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No critical events documented.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $events->links() }}
@stop
