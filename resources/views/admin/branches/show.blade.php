@extends('layouts.adminlte')

@section('page_title', $branch->name)

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Branch Details') }}</h3>
            <div class="card-tools">
                <a href="{{ route('admin.organizations.branches.edit', [$organization, $branch]) }}" class="btn btn-warning btn-sm">{{ __('Edit') }}</a>
                <a href="{{ route('admin.organizations.branches.departments.index', [$organization, $branch]) }}" class="btn btn-info btn-sm">{{ __('Manage Departments') }}</a>
                <a href="{{ route('admin.organizations.branches.index', $organization) }}" class="btn btn-secondary btn-sm">{{ __('Back') }}</a>
            </div>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">{{ __('Name') }}</dt>
                <dd class="col-sm-9">{{ $branch->name }}</dd>

                <dt class="col-sm-3">{{ __('Code') }}</dt>
                <dd class="col-sm-9">{{ $branch->code }}</dd>

                <dt class="col-sm-3">{{ __('Slug') }}</dt>
                <dd class="col-sm-9">{{ $branch->slug }}</dd>

                <dt class="col-sm-3">{{ __('Email') }}</dt>
                <dd class="col-sm-9">{{ $branch->email ?? '-' }}</dd>

                <dt class="col-sm-3">{{ __('Phone') }}</dt>
                <dd class="col-sm-9">{{ $branch->phone ?? '-' }}</dd>

                <dt class="col-sm-3">{{ __('Address') }}</dt>
                <dd class="col-sm-9">{{ $branch->address ?? '-' }}</dd>

                <dt class="col-sm-3">{{ __('Status') }}</dt>
                <dd class="col-sm-9">
                    <span class="badge {{ $branch->is_active ? 'bg-success' : 'bg-danger' }}">
                        {{ $branch->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </dd>

                <dt class="col-sm-3">{{ __('Organization') }}</dt>
                <dd class="col-sm-9">{{ $organization->name }}</dd>

                <dt class="col-sm-3">{{ __('Created') }}</dt>
                <dd class="col-sm-9">{{ $branch->created_at->format('Y-m-d H:i') }}</dd>
            </dl>
        </div>
    </div>
@stop
