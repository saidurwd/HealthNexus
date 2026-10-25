@extends('layouts.adminlte')

@section('page_title', 'Departments')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Departments') }}</h3>
            <div class="card-tools">
                <span class="text-muted small">{{ __('Create departments from within a Organization → Branch') }}</span>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Organization') }}</th>
                        <th>{{ __('Branch') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $department)
                        <tr>
                            <td>{{ $department->name }}</td>
                            <td>{{ $department->code }}</td>
                            <td>{{ $department->organization->name ?? '-' }}</td>
                            <td>{{ $department->branch->name ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $department->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $department->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.organizations.branches.departments.show', [$department->organization, $department->branch, $department]) }}" class="btn btn-xs btn-info">{{ __('View') }}</a>
                                <a href="{{ route('admin.organizations.branches.departments.edit', [$department->organization, $department->branch, $department]) }}" class="btn btn-xs btn-warning">{{ __('Edit') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ __('No departments found.') }}</td>
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
