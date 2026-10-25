@extends('layouts.adminlte')

@section('page_title', $organization->name)

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Organization Details</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.organizations.edit', $organization) }}" class="btn btn-warning btn-sm">Edit</a>
                    <a href="{{ route('admin.organizations.branches.index', $organization) }}" class="btn btn-info btn-sm">Manage Branches</a>
                    <a href="{{ route('admin.users.organization-index', $organization) }}" class="btn btn-success btn-sm">Manage Users</a>
                    <a href="{{ route('admin.organizations.index') }}" class="btn btn-secondary btn-sm">Back</a>
                </div>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $organization->name }}</dd>

                <dt class="col-sm-3">Code</dt>
                <dd class="col-sm-9">{{ $organization->code }}</dd>

                <dt class="col-sm-3">Slug</dt>
                <dd class="col-sm-9">{{ $organization->slug }}</dd>

                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $organization->email ?? '-' }}</dd>

                <dt class="col-sm-3">Phone</dt>
                <dd class="col-sm-9">{{ $organization->phone ?? '-' }}</dd>

                <dt class="col-sm-3">Address</dt>
                <dd class="col-sm-9">{{ $organization->address ?? '-' }}</dd>

                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">
                    <span class="badge {{ $organization->is_active ? 'bg-success' : 'bg-danger' }}">
                        {{ $organization->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </dd>

                <dt class="col-sm-3">Created</dt>
                <dd class="col-sm-9">{{ $organization->created_at->format('Y-m-d H:i') }}</dd>
            </dl>
        </div>
    </div>
@stop
