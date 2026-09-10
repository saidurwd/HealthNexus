@extends('layouts.adminlte')

@section('page_title', 'Edit Role')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit Role</h3>
        </div>
        <form action="{{ route('admin.roles.update', $role) }}" method="post">
            @csrf
            @method('put')
            <div class="card-body">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $role->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Permissions</label>
                    @php
                        $allPermissions = \Spatie\Permission\Models\Permission::orderBy('name')->get(['id', 'name']);
                        $oldPermissions = old('permissions', $role->permissions->pluck('name')->toArray());
                    @endphp
                    @foreach($allPermissions as $permission)
                        <div class="form-check">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="perm_{{ $permission->id }}" class="form-check-input" {{ in_array($permission->name, $oldPermissions) ? 'checked' : '' }}>
                            <label for="perm_{{ $permission->id }}" class="form-check-label">{{ $permission->name }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Role</button>
            </div>
        </form>
    </div>
@stop
