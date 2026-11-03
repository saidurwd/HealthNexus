@extends('layouts.adminlte')

@section('page_title', $organization->name . ' - Branches')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Branches - {{ $organization->name }}</h3>
            <div class="card-tools">
                <a href="{{ route('admin.organizations.branches.create', $organization) }}" class="btn btn-primary btn-sm">{{ __('New Branch') }}</a>
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
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($branches as $branch)
                        <tr>
                            <td>{{ $branch->name }}</td>
                            <td>{{ $branch->code }}</td>
                            <td>{{ $branch->email ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $branch->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $branch->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.organizations.branches.show', [$organization, $branch]) }}" class="btn btn-xs btn-info">{{ __('View') }}</a>
                                <a href="{{ route('admin.organizations.branches.edit', [$organization, $branch]) }}" class="btn btn-xs btn-warning">{{ __('Edit') }}</a>
                                <form action="{{ route('admin.organizations.branches.destroy', [$organization, $branch]) }}" method="post" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-xs btn-danger">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">{{ __('No branches found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <a href="{{ route('admin.organizations.index') }}" class="btn btn-secondary">{{ __('Back to Organizations') }}</a>
        </div>
    </div>
@stop
