@extends('layouts.adminlte')

@section('page_title', $company->name . ' - Branches')

@section('content_top_nav_left')
    <a href="{{ route('admin.companies.branches.create', $company) }}" class="btn btn-primary btn-sm">New Branch</a>
@stop

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Branches - {{ $company->name }}</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Actions</th>
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
                                <a href="{{ route('admin.companies.branches.show', [$company, $branch]) }}" class="btn btn-xs btn-info">View</a>
                                <a href="{{ route('admin.companies.branches.edit', [$company, $branch]) }}" class="btn btn-xs btn-warning">Edit</a>
                                <form action="{{ route('admin.companies.branches.destroy', [$company, $branch]) }}" method="post" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-xs btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No branches found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <a href="{{ route('admin.companies.index') }}" class="btn btn-secondary">Back to Companies</a>
        </div>
    </div>
@stop
