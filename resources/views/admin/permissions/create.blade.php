@extends('layouts.adminlte')

@section('page_title', 'Create Permission')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Create Permission</h3>
        </div>
        <form action="{{ route('admin.permissions.store') }}" method="post">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Permission</button>
            </div>
        </form>
    </div>
@stop
