@extends('layouts.adminlte')

@section('page_title', 'Edit User')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit User</h3>
        </div>
        <form action="{{ route('admin.companies.users.update', [$company, $user]) }}" method="post">
            @csrf
            @method('put')
            <div class="card-body">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="timezone" class="form-label">Timezone</label>
                    <input type="text" name="timezone" id="timezone" value="{{ old('timezone', $user->timezone) }}" class="form-control @error('timezone') is-invalid @enderror">
                    @error('timezone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="locale" class="form-label">Locale</label>
                    <input type="text" name="locale" id="locale" value="{{ old('locale', $user->locale) }}" class="form-control @error('locale') is-invalid @enderror">
                    @error('locale') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} class="form-check-input">
                        <label for="is_active" class="form-check-label">Active</label>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.companies.users.index', $company) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
@stop
