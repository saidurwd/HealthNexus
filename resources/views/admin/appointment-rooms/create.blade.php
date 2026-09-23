@extends('layouts.adminlte')

@section('page_title', 'Add Room')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Add Room</h2>
            <a href="{{ route('admin.appointment-rooms.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>
        <form method="POST" action="{{ route('admin.appointment-rooms.store') }}">
            @csrf
            @include('admin.appointment-rooms._form')
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Save Room</button>
            </div>
        </form>
    </div>
@endsection
