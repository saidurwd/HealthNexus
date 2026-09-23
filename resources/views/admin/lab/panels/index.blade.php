@extends('layouts.adminlte')

@section('page_title', 'Test Panels')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Test Panels</h3>
            <div class="card-tools">
                @can('lab.test.create')
                    <a href="{{ route('admin.lab.panels.create') }}" class="btn btn-primary btn-sm">New Panel</a>
                @endcan
                <a href="{{ route('admin.lab.tests.index') }}" class="btn btn-secondary btn-sm">Tests</a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr><th>Code</th><th>Name</th><th># Tests</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($panels as $panel)
                        <tr>
                            <td>{{ $panel->code }}</td>
                            <td>{{ $panel->name }}</td>
                            <td>{{ $panel->items_count }}</td>
                            <td><span class="badge {{ $panel->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $panel->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                @can('lab.test.update')
                                    <a href="{{ route('admin.lab.panels.edit', $panel) }}" class="btn btn-xs btn-warning">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No panels found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $panels->links() }}</div>
    </div>
@stop
