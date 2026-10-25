@extends('layouts.adminlte')

@section('page_title', $department->name)

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Department Details') }}</h3>
            <div class="card-tools">
                <a href="{{ route('admin.organizations.branches.departments.edit', [$organization, $branch, $department]) }}" class="btn btn-warning btn-sm">{{ __('Edit') }}</a>
                <a href="{{ route('admin.organizations.branches.departments.index', [$organization, $branch]) }}" class="btn btn-secondary btn-sm">{{ __('Back') }}</a>
            </div>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">{{ __('Name') }}</dt>
                <dd class="col-sm-9">{{ $department->name }}</dd>

                <dt class="col-sm-3">{{ __('Code') }}</dt>
                <dd class="col-sm-9">{{ $department->code }}</dd>

                <dt class="col-sm-3">{{ __('Description') }}</dt>
                <dd class="col-sm-9">{{ $department->description ?? '-' }}</dd>

                <dt class="col-sm-3">{{ __('Head of Department') }}</dt>
                <dd class="col-sm-9">{{ $department->head_of_department ?? '-' }}</dd>

                <dt class="col-sm-3">{{ __('Email') }}</dt>
                <dd class="col-sm-9">{{ $department->email ?? '-' }}</dd>

                <dt class="col-sm-3">{{ __('Phone') }}</dt>
                <dd class="col-sm-9">{{ $department->phone ?? '-' }}</dd>

                <dt class="col-sm-3">{{ __('Status') }}</dt>
                <dd class="col-sm-9">
                    <span class="badge {{ $department->is_active ? 'bg-success' : 'bg-danger' }}">
                        {{ $department->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </dd>

                <dt class="col-sm-3">{{ __('Branch') }}</dt>
                <dd class="col-sm-9">{{ $branch->name }} ({{ $organization->name }})</dd>

                <dt class="col-sm-3">{{ __('Created') }}</dt>
                <dd class="col-sm-9">{{ $department->created_at->format('Y-m-d H:i') }}</dd>
            </dl>
        </div>
    </div>
@stop
