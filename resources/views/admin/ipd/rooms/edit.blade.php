@extends('layouts.adminlte')

@section('page_title', 'Edit Room')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Edit Room</h3></div>
        <form action="{{ route('admin.ipd.rooms.update', $room) }}" method="post">
            @csrf
            @method('PUT')
            @include('admin.ipd.rooms._form')
            <div class="card-footer">
                <a href="{{ route('admin.ipd.rooms.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Room</button>
            </div>
        </form>
    </div>
@stop
