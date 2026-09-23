@extends('layouts.adminlte')

@section('page_title', 'Edit Radiology Procedure')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Edit Procedure — {{ $procedure->name }}</h3></div>
        <form action="{{ route('admin.radiology.procedures.update', $procedure) }}" method="post">
            @csrf
            @method('PUT')
            @include('admin.radiology.procedures._form', ['procedure' => $procedure])
            <div class="card-footer">
                <a href="{{ route('admin.radiology.procedures.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Procedure</button>
            </div>
        </form>
    </div>
@stop
