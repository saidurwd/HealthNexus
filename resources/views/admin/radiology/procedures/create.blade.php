@extends('layouts.adminlte')

@section('page_title', 'New Radiology Procedure')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">New Radiology Procedure</h3></div>
        <form action="{{ route('admin.radiology.procedures.store') }}" method="post">
            @csrf
            @include('admin.radiology.procedures._form', ['procedure' => null])
            <div class="card-footer">
                <a href="{{ route('admin.radiology.procedures.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Procedure</button>
            </div>
        </form>
    </div>
@stop
