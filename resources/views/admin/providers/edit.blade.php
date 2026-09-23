@extends('layouts.adminlte')

@section('page_title', 'Edit Provider')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Edit Provider</h2>
            <a href="{{ route('admin.providers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>

        <form method="POST" action="{{ route('admin.providers.update', $provider) }}">
            @csrf
            @method('put')
            @include('admin.providers._form')
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
@endsection
