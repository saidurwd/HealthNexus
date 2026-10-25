@extends('layouts.adminlte')

@section('page_title', 'Organizations')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Organizations') }}</h3>
            <div class="card-tools">
                <a href="{{ route('admin.organizations.create') }}" class="btn btn-primary btn-sm">{{ __('New Organization') }}</a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Branches') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($organizations as $organization)
                        <tr>
                            <td>{{ $organization->name }}</td>
                            <td>{{ $organization->code }}</td>
                            <td>{{ $organization->email ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $organization->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $organization->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.organizations.branches.index', $organization) }}" class="btn btn-xs btn-success">
                                    {{ $organization->branches_count ?? $organization->branches()->count() }} Branches
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('admin.organizations.show', $organization) }}" class="btn btn-xs btn-info">{{ __('View') }}</a>
                                <a href="{{ route('admin.organizations.edit', $organization) }}" class="btn btn-xs btn-warning">{{ __('Edit') }}</a>
                                <form action="{{ route('admin.organizations.destroy', $organization) }}" method="post" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-xs btn-danger">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ __('No organizations found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $organizations->links() }}
        </div>
    </div>
@stop
