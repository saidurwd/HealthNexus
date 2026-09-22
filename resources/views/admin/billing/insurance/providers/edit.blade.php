@extends('layouts.adminlte')

@section('page_title', 'Edit Insurance Provider')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Edit Insurance Provider</h3></div>
        <form action="{{ route('admin.billing.insurance.providers.update', $provider) }}" method="post">
            @csrf
            @method('put')
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name', $provider->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" value="{{ old('code', $provider->code) }}" class="form-control @error('code') is-invalid @enderror" required>
                    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Contact Name</label>
                    <input type="text" name="contact_name" value="{{ old('contact_name', $provider->contact_name) }}" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Contact Email</label>
                    <input type="email" name="contact_email" value="{{ old('contact_email', $provider->contact_email) }}" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Contact Phone</label>
                    <input type="text" name="contact_phone" value="{{ old('contact_phone', $provider->contact_phone) }}" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control" required>
                        <option value="active" {{ old('status', $provider->status) == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $provider->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.billing.insurance.providers.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Provider</button>
            </div>
        </form>
    </div>
@stop
