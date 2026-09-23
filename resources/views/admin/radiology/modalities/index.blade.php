@extends('layouts.adminlte')

@section('page_title', 'Modalities')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Modalities</h3>
            <div class="card-tools">
                @can('radiology.modality.create')
                    <a href="{{ route('admin.radiology.modalities.create') }}" class="btn btn-primary btn-sm">Register Modality</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Section</th><th>Room</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($modalities as $modality)
                        <tr>
                            <td>{{ $modality->code }}</td>
                            <td>{{ $modality->name }}</td>
                            <td>{{ $modality->modality_type }}</td>
                            <td>{{ $modality->section?->name }}</td>
                            <td>{{ $modality->room?->name }}</td>
                            <td><span class="badge {{ $modality->status === 'online' ? 'bg-success' : ($modality->status === 'disabled' ? 'bg-secondary' : 'bg-warning') }}">{{ ucfirst($modality->status) }}</span></td>
                            <td>
                                @can('radiology.modality.update')
                                    <a href="{{ route('admin.radiology.modalities.edit', $modality) }}" class="btn btn-xs btn-warning">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No modalities registered.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $modalities->links() }}</div>
    </div>
@stop
