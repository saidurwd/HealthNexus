@extends('layouts.adminlte')

@section('page_title', $branch->name . ' - Departments')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Departments - {{ $organization->name }} / {{ $branch->name }}</h3>
            <div class="card-tools">
                <a href="{{ route('admin.organizations.branches.departments.create', [$organization, $branch]) }}" class="btn btn-primary btn-sm">{{ __('New Department') }}</a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Head') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
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
                                <a href="{{ route('admin.organizations.branches.departments.show', [$organization, $branch, $department]) }}" class="btn btn-xs btn-info">{{ __('View') }}</a>
                                <a href="{{ route('admin.organizations.branches.departments.edit', [$organization, $branch, $department]) }}" class="btn btn-xs btn-warning">{{ __('Edit') }}</a>
                                <form action="{{ route('admin.organizations.branches.departments.destroy', [$organization, $branch, $department]) }}" method="post" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-xs btn-danger">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">{{ __('No departments found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $departments->links() }}
            <a href="{{ route('admin.organizations.branches.index', $organization) }}" class="btn btn-secondary">{{ __('Back to Branches') }}</a>
        </div>
    </div>
@stop
