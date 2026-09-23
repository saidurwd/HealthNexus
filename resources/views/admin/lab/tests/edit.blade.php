@extends('layouts.adminlte')

@section('page_title', 'Edit Lab Test')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Edit Lab Test — {{ $test->name }}</h3></div>
        <form action="{{ route('admin.lab.tests.update', $test) }}" method="post">
            @csrf
            @method('PUT')
            @include('admin.lab.tests._form', ['test' => $test])
            <div class="card-footer">
                <a href="{{ route('admin.lab.tests.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Test</button>
            </div>
        </form>
    </div>
@stop
