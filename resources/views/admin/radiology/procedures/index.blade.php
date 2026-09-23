@extends('layouts.adminlte')

@section('page_title', 'Radiology Procedures')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Procedure Catalog</h3>
            <div class="card-tools">
                @can('radiology.procedure.create')
                    <a href="{{ route('admin.radiology.procedures.create') }}" class="btn btn-primary btn-sm">New Procedure</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by name or code...">
            </form>
            <table class="table table-striped">
                <thead>
                    <tr><th>Code</th><th>Name</th><th>Modality</th><th>Body Part</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($procedures as $procedure)
                        <tr>
                            <td>{{ $procedure->code }}</td>
                            <td>{{ $procedure->name }}</td>
                            <td>{{ $procedure->modality_type }}</td>
                            <td>{{ $procedure->bodyPart?->name }}</td>
                            <td>
                                <span class="badge {{ $procedure->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $procedure->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                @can('radiology.procedure.update')
                                    <a href="{{ route('admin.radiology.procedures.edit', $procedure) }}" class="btn btn-xs btn-warning">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No radiology procedures found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $procedures->links() }}</div>
    </div>
@stop
