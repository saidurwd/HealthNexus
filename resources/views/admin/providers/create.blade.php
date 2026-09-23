@extends('layouts.adminlte')

@section('page_title', 'Add Provider')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Add Provider</h2>
            <a href="{{ route('admin.providers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>

        <form method="POST" action="{{ route('admin.providers.store') }}">
            @csrf
            @include('admin.providers._form')
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Save Provider</button>
            </div>
        </form>
    </div>
@endsection
