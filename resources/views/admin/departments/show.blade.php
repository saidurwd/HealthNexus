@extends('layouts.adminlte')

@section('page_title', $department->name)

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Department Details</h3>
            <div class="card-tools">
                <a href="{{ route('admin.companies.branches.departments.edit', [$company, $branch, $department]) }}" class="btn btn-warning btn-sm">Edit</a>
                <a href="{{ route('admin.companies.branches.departments.index', [$company, $branch]) }}" class="btn btn-secondary btn-sm">Back</a>
            </div>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $department->name }}</dd>

                <dt class="col-sm-3">Code</dt>
                <dd class="col-sm-9">{{ $department->code }}</dd>

                <dt class="col-sm-3">Description</dt>
                <dd class="col-sm-9">{{ $department->description ?? '-' }}</dd>

                <dt class="col-sm-3">Head of Department</dt>
                <dd class="col-sm-9">{{ $department->head_of_department ?? '-' }}</dd>

                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $department->email ?? '-' }}</dd>

                <dt class="col-sm-3">Phone</dt>
                <dd class="col-sm-9">{{ $department->phone ?? '-' }}</dd>

                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">
                    <span class="badge {{ $department->is_active ? 'bg-success' : 'bg-danger' }}">
                        {{ $department->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </dd>

                <dt class="col-sm-3">Branch</dt>
                <dd class="col-sm-9">{{ $branch->name }} ({{ $company->name }})</dd>

                <dt class="col-sm-3">Created</dt>
                <dd class="col-sm-9">{{ $department->created_at->format('Y-m-d H:i') }}</dd>
            </dl>
        </div>
    </div>
@stop
