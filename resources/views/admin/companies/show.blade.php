@extends('layouts.adminlte')

@section('page_title', $company->name)

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Company Details</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.companies.edit', $company) }}" class="btn btn-warning btn-sm">Edit</a>
                    <a href="{{ route('admin.companies.branches.index', $company) }}" class="btn btn-info btn-sm">Manage Branches</a>
                    <a href="{{ route('admin.users.company-index', $company) }}" class="btn btn-success btn-sm">Manage Users</a>
                    <a href="{{ route('admin.companies.index') }}" class="btn btn-secondary btn-sm">Back</a>
                </div>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $company->name }}</dd>

                <dt class="col-sm-3">Code</dt>
                <dd class="col-sm-9">{{ $company->code }}</dd>

                <dt class="col-sm-3">Slug</dt>
                <dd class="col-sm-9">{{ $company->slug }}</dd>

                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $company->email ?? '-' }}</dd>

                <dt class="col-sm-3">Phone</dt>
                <dd class="col-sm-9">{{ $company->phone ?? '-' }}</dd>

                <dt class="col-sm-3">Address</dt>
                <dd class="col-sm-9">{{ $company->address ?? '-' }}</dd>

                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">
                    <span class="badge {{ $company->is_active ? 'bg-success' : 'bg-danger' }}">
                        {{ $company->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </dd>

                <dt class="col-sm-3">Created</dt>
                <dd class="col-sm-9">{{ $company->created_at->format('Y-m-d H:i') }}</dd>
            </dl>
        </div>
    </div>
@stop
