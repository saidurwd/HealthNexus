@extends('layouts.adminlte')

@section('page_title', 'Edit Test Panel')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Edit Panel — {{ $panel->name }}</h3></div>
        <form action="{{ route('admin.lab.panels.update', $panel) }}" method="post">
            @csrf
            @method('PUT')
            @include('admin.lab.panels._form', ['panel' => $panel])
            <div class="card-footer">
                <a href="{{ route('admin.lab.panels.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Panel</button>
            </div>
        </form>
    </div>
@stop
