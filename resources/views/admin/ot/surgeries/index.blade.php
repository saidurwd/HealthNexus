@extends('layouts.adminlte')

@section('page_title', 'Surgical Cases')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Surgical Cases') }}</h3>
            @can('ot.surgery.create')
                <a href="{{ route('admin.ot.surgeries.create') }}" class="btn btn-primary float-right">{{ __('New Case') }}</a>
            @endcan
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>{{ __('Surgery #') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Procedure') }}</th><th>{{ __('Theatre') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($surgeries as $surgery)
                        <tr>
                            <td>{{ $surgery->surgery_number }}</td>
                            <td>{{ $surgery->patient?->full_name }}</td>
                            <td>{{ $surgery->primaryProcedure?->name ?? '—' }}</td>
                            <td>{{ $surgery->theatre?->name ?? '—' }}</td>
                            <td><span class="badge bg-info">{{ ucwords(str_replace('_',' ',$surgery->status)) }}</span></td>
                            <td><a href="{{ route('admin.ot.surgeries.show', $surgery) }}" class="btn btn-xs btn-info">{{ __('Open') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">{{ __('No surgical cases found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $surgeries->links() }}</div>
    </div>
@stop
