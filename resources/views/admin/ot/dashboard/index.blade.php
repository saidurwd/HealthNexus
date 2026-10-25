@extends('layouts.adminlte')

@section('page_title', 'OT Dashboard')

@section('page_content')
    <div class="row">
        @foreach($metrics as $label => $value)
            <div class="col-md-3 col-sm-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h3>{{ $value }}</h3>
                        <div class="text-muted">{{ ucwords(str_replace('_', ' ', $label)) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Today\'s Operating List') }}</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>{{ __('Time') }}</th><th>{{ __('Theatre') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Procedure') }}</th><th>{{ __('Surgeon') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($todaysList as $surgery)
                        <tr>
                            <td>{{ $surgery->scheduled_start_at?->format('H:i') ?? '—' }}</td>
                            <td>{{ $surgery->theatre?->name ?? '—' }}</td>
                            <td>{{ $surgery->patient?->full_name }}</td>
                            <td>{{ $surgery->primaryProcedure?->name ?? '—' }}</td>
                            <td>{{ $surgery->primarySurgeon?->name ?? '—' }}</td>
                            <td><span class="badge bg-info">{{ ucwords(str_replace('_', ' ', $surgery->status)) }}</span></td>
                            <td><a href="{{ route('admin.ot.surgeries.show', $surgery) }}" class="btn btn-xs btn-info">{{ __('Open') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">{{ __('No surgeries scheduled today.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
