@extends('layouts.adminlte')

@section('page_title', $branch->name . ' - Departments')

@section('content_top_nav_left')
    <a href="{{ route('admin.companies.branches.departments.create', [$company, $branch]) }}" class="btn btn-primary btn-sm">New Department</a>
@stop

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Departments - {{ $company->name }} / {{ $branch->name }}</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Head</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $department)
                        <tr>
                            <td>{{ $department->name }}</td>
                            <td>{{ $department->code }}</td>
                            <td>{{ $department->head_of_department ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $department->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $department->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.companies.branches.departments.show', [$company, $branch, $department]) }}" class="btn btn-xs btn-info">View</a>
                                <a href="{{ route('admin.companies.branches.departments.edit', [$company, $branch, $department]) }}" class="btn btn-xs btn-warning">Edit</a>
                                <form action="{{ route('admin.companies.branches.departments.destroy', [$company, $branch, $department]) }}" method="post" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-xs btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No departments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <a href="{{ route('admin.companies.branches.index', $company) }}" class="btn btn-secondary">Back to Branches</a>
        </div>
    </div>
@stop
