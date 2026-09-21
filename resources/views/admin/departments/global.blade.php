@extends('layouts.adminlte')

@section('page_title', 'Departments')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Departments</h3>
            <div class="card-tools">
                <span class="text-muted small">Create departments from within a Company → Branch</span>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Company</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $department)
                        <tr>
                            <td>{{ $department->name }}</td>
                            <td>{{ $department->code }}</td>
                            <td>{{ $department->company->name ?? '-' }}</td>
                            <td>{{ $department->branch->name ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $department->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $department->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.companies.branches.departments.show', [$department->company, $department->branch, $department]) }}" class="btn btn-xs btn-info">View</a>
                                <a href="{{ route('admin.companies.branches.departments.edit', [$department->company, $department->branch, $department]) }}" class="btn btn-xs btn-warning">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No departments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $departments->links() }}
        </div>
    </div>
@stop
