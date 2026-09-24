@extends('layouts.adminlte')

@section('page_title', 'New Billing Category')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">New Billing Category</h3></div>
        <form action="{{ route('admin.billing.categories.store') }}" method="post">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" value="{{ old('code') }}" class="form-control @error('code') is-invalid @enderror" required>
                    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control">{{ old('description') }}</textarea>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.billing.categories.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Category</button>
            </div>
        </form>
    </div>
@stop
