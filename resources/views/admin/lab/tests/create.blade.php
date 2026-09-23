@extends('layouts.adminlte')

@section('page_title', 'New Lab Test')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">New Lab Test</h3></div>
        <form action="{{ route('admin.lab.tests.store') }}" method="post">
            @csrf
            @include('admin.lab.tests._form', ['test' => null])
            <div class="card-footer">
                <a href="{{ route('admin.lab.tests.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Test</button>
            </div>
        </form>
    </div>
@stop
