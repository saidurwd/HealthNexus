@extends('layouts.adminlte')

@section('page_title', 'New Brand')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('New Brand') }}</h3></div>
        <form action="{{ route('admin.pharmacy.brands.store') }}" method="post">
            @csrf
            @include('admin.pharmacy.brands._form', ['brand' => null])
            <div class="card-footer">
                <a href="{{ route('admin.pharmacy.brands.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('Save Brand') }}</button>
            </div>
        </form>
    </div>
@stop
