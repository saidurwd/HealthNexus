@extends('layouts.adminlte')

@section('page_title', $user->name)

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">User Details</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-sm">Edit</a>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm">Back</a>
                </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 text-center">
                    @if ($user->profile_picture)
                        <img src="{{ asset('storage/' . $user->profile_picture) }}" alt="{{ $user->name }}" class="img-fluid rounded" style="max-width: 200px;">
                    @else
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto" style="width: 200px; height: 200px; font-size: 80px; color: white;">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div class="col-md-8">
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
        </div>
    </div>
@stop
