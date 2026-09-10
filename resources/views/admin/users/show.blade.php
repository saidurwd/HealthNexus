@extends('layouts.adminlte')

@section('page_title', $user->name)

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">User Details</h3>
            <div class="card-tools">
                <a href="{{ route('admin.companies.users.edit', [$company, $user]) }}" class="btn btn-warning btn-sm">Edit</a>
                <a href="{{ route('admin.companies.users.index', $company) }}" class="btn btn-secondary btn-sm">Back</a>
            </div>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $user->name }}</dd>

                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $user->email }}</dd>

                <dt class="col-sm-3">Phone</dt>
                <dd class="col-sm-9">{{ $user->phone ?? '-' }}</dd>

                <dt class="col-sm-3">Timezone</dt>
                <dd class="col-sm-9">{{ $user->timezone ?? '-' }}</dd>

                <dt class="col-sm-3">Locale</dt>
                <dd class="col-sm-9">{{ $user->locale ?? '-' }}</dd>

                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">
                    <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-danger' }}">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </dd>

                <dt class="col-sm-3">Last Login</dt>
                <dd class="col-sm-9">{{ $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i') : 'Never' }}</dd>

                <dt class="col-sm-3">Created</dt>
                <dd class="col-sm-9">{{ $user->created_at->format('Y-m-d H:i') }}</dd>
            </dl>
        </div>
    </div>
@stop
