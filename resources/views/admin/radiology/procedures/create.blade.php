@extends('layouts.adminlte')

@section('page_title', 'New Radiology Procedure')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('New Radiology Procedure') }}</h3></div>
        <form action="{{ route('admin.radiology.procedures.store') }}" method="post">
            @csrf
            @include('admin.radiology.procedures._form', ['procedure' => null])
            <div class="card-footer">
                <a href="{{ route('admin.radiology.procedures.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('Save Procedure') }}</button>
            </div>
        </form>
    </div>
@stop
