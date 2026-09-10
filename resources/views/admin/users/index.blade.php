@extends('layouts.adminlte')

@section('page_title', 'Users - ' . $company->name)

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Users - {{ $company->name }}</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.companies.users.show', [$company, $user]) }}" class="btn btn-xs btn-info">View</a>
                                <a href="{{ route('admin.companies.users.edit', [$company, $user]) }}" class="btn btn-xs btn-warning">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No users found.</td>
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
