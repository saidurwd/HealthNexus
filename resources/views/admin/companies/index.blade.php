@extends('layouts.adminlte')

@section('page_title', 'Companies')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Companies</h3>
            <div class="card-tools">
                <a href="{{ route('admin.companies.create') }}" class="btn btn-primary btn-sm">New Company</a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Branches</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $company)
                        <tr>
                            <td>{{ $company->name }}</td>
                            <td>{{ $company->code }}</td>
                            <td>{{ $company->email ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $company->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $company->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.companies.branches.index', $company) }}" class="btn btn-xs btn-success">
                                    {{ $company->branches_count ?? $company->branches()->count() }} Branches
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('admin.companies.show', $company) }}" class="btn btn-xs btn-info">View</a>
                                <a href="{{ route('admin.companies.edit', $company) }}" class="btn btn-xs btn-warning">Edit</a>
                                <form action="{{ route('admin.companies.destroy', $company) }}" method="post" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-xs btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No companies found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $companies->links() }}
        </div>
    </div>
@stop
