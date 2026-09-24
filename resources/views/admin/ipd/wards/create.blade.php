@extends('layouts.adminlte')

@section('page_title', 'New Ward')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">New Ward</h3></div>
        <form action="{{ route('admin.ipd.wards.store') }}" method="post">
            @csrf
            @include('admin.ipd.wards._form', ['ward' => null])
            <div class="card-footer">
                <a href="{{ route('admin.ipd.wards.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Ward</button>
            </div>
        </form>
    </div>
@stop
