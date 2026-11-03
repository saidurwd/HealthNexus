@extends('layouts.adminlte')

@section('page_title', 'Edit Brand')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Edit Brand') }}</h3></div>
        <form action="{{ route('admin.pharmacy.brands.update', $brand) }}" method="post">
            @csrf
            @method('PUT')
            @include('admin.pharmacy.brands._form')
            <div class="card-footer">
                <a href="{{ route('admin.pharmacy.brands.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('Update Brand') }}</button>
            </div>
        </form>
    </div>
@stop
