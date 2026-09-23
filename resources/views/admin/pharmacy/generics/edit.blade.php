@extends('layouts.adminlte')

@section('page_title', 'Edit Generic')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Edit Generic</h3></div>
        <form action="{{ route('admin.pharmacy.generics.update', $generic) }}" method="post">
            @csrf
            @method('PUT')
            @include('admin.pharmacy.generics._form')
            <div class="card-footer">
                <a href="{{ route('admin.pharmacy.generics.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Generic</button>
            </div>
        </form>
    </div>
@stop
