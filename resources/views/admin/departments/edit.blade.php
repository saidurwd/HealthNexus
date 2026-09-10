@extends('layouts.adminlte')

@section('page_title', 'Edit Department')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit Department</h3>
        </div>
        <form action="{{ route('admin.companies.branches.departments.update', [$company, $branch, $department]) }}" method="post">
            @csrf
            @method('put')
            <div class="card-body">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $department->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="code" class="form-label">Code</label>
                    <input type="text" name="code" id="code" value="{{ old('code', $department->code) }}" class="form-control @error('code') is-invalid @enderror" required>
                    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror">{{ old('description', $department->description) }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="head_of_department" class="form-label">Head of Department</label>
                    <input type="text" name="head_of_department" id="head_of_department" value="{{ old('head_of_department', $department->head_of_department) }}" class="form-control @error('head_of_department') is-invalid @enderror">
                    @error('head_of_department') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $department->email) }}" class="form-control @error('email') is-invalid @enderror">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $department->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $department->is_active) ? 'checked' : '' }} class="form-check-input">
                        <label for="is_active" class="form-check-label">Active</label>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.companies.branches.departments.index', [$company, $branch]) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Department</button>
            </div>
        </form>
    </div>
@stop
