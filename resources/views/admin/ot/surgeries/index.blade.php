@extends('layouts.adminlte')

@section('page_title', 'Surgical Cases')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Surgical Cases</h3>
            @can('ot.surgery.create')
                <a href="{{ route('admin.ot.surgeries.create') }}" class="btn btn-primary float-right">New Case</a>
            @endcan
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Surgery #</th><th>Patient</th><th>Procedure</th><th>Theatre</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($surgeries as $surgery)
                        <tr>
                            <td>{{ $surgery->surgery_number }}</td>
                            <td>{{ $surgery->patient?->full_name }}</td>
                            <td>{{ $surgery->primaryProcedure?->name ?? '—' }}</td>
                            <td>{{ $surgery->theatre?->name ?? '—' }}</td>
                            <td><span class="badge bg-info">{{ ucwords(str_replace('_',' ',$surgery->status)) }}</span></td>
                            <td><a href="{{ route('admin.ot.surgeries.show', $surgery) }}" class="btn btn-xs btn-info">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No surgical cases found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $surgeries->links() }}</div>
    </div>
@stop
