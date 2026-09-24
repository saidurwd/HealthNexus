@extends('layouts.adminlte')

@section('page_title', 'Edit Ward')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Edit Ward</h3></div>
        <form action="{{ route('admin.ipd.wards.update', $ward) }}" method="post">
            @csrf
            @method('PUT')
            @include('admin.ipd.wards._form')
            <div class="card-footer">
                <a href="{{ route('admin.ipd.wards.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Ward</button>
            </div>
        </form>
    </div>
@stop
