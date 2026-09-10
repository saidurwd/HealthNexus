@extends('layouts.adminlte')

@section('page_title', $branch->name)

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Branch Details</h3>
            <div class="card-tools">
                <a href="{{ route('admin.companies.branches.edit', [$company, $branch]) }}" class="btn btn-warning btn-sm">Edit</a>
                <a href="{{ route('admin.companies.branches.index', $company) }}" class="btn btn-secondary btn-sm">Back</a>
            </div>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $branch->name }}</dd>

                <dt class="col-sm-3">Code</dt>
                <dd class="col-sm-9">{{ $branch->code }}</dd>

                <dt class="col-sm-3">Slug</dt>
                <dd class="col-sm-9">{{ $branch->slug }}</dd>

                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $branch->email ?? '-' }}</dd>

                <dt class="col-sm-3">Phone</dt>
                <dd class="col-sm-9">{{ $branch->phone ?? '-' }}</dd>

                <dt class="col-sm-3">Address</dt>
                <dd class="col-sm-9">{{ $branch->address ?? '-' }}</dd>

                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">
                    <span class="badge {{ $branch->is_active ? 'bg-success' : 'bg-danger' }}">
                        {{ $branch->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </dd>

                <dt class="col-sm-3">Company</dt>
                <dd class="col-sm-9">{{ $company->name }}</dd>

                <dt class="col-sm-3">Created</dt>
                <dd class="col-sm-9">{{ $branch->created_at->format('Y-m-d H:i') }}</dd>
            </dl>
        </div>
    </div>
@stop
