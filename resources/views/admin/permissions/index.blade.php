@extends('layouts.adminlte')

@section('page_title', 'Permissions')

@section('content_top_nav_left')
    <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary btn-sm">New Permission</a>
@stop

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Permissions</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($permissions as $permission)
                        <tr>
                            <td>{{ $permission->name }}</td>
                            <td>
                                <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn btn-xs btn-warning">Edit</a>
                                <form action="{{ route('admin.permissions.destroy', $permission) }}" method="post" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-xs btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center">No permissions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $permissions->links() }}
        </div>
    </div>
@stop
