@extends('layouts.adminlte')

@section('page_title', 'New Room')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">New Room</h3></div>
        <form action="{{ route('admin.ipd.rooms.store') }}" method="post">
            @csrf
            @include('admin.ipd.rooms._form', ['room' => null])
            <div class="card-footer">
                <a href="{{ route('admin.ipd.rooms.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Room</button>
            </div>
        </form>
    </div>
@stop
