@extends('layouts.adminlte')

@section('page_title', 'New Bed')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('New Bed') }}</h3></div>
        <form action="{{ route('admin.ipd.beds.store') }}" method="post">
            @csrf
            @include('admin.ipd.beds._form', ['bed' => null])
            <div class="card-footer">
                <a href="{{ route('admin.ipd.beds.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('Save Bed') }}</button>
            </div>
        </form>
    </div>
@stop
