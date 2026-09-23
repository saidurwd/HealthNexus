@extends('layouts.adminlte')

@section('page_title', 'New Test Panel')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">New Test Panel</h3></div>
        <form action="{{ route('admin.lab.panels.store') }}" method="post">
            @csrf
            @include('admin.lab.panels._form', ['panel' => null])
            <div class="card-footer">
                <a href="{{ route('admin.lab.panels.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Panel</button>
            </div>
        </form>
    </div>
@stop
