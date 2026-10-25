@extends('layouts.adminlte')

@section('page_title', 'New Generic')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('New Generic') }}</h3></div>
        <form action="{{ route('admin.pharmacy.generics.store') }}" method="post">
            @csrf
            @include('admin.pharmacy.generics._form', ['generic' => null])
            <div class="card-footer">
                <a href="{{ route('admin.pharmacy.generics.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('Save Generic') }}</button>
            </div>
        </form>
    </div>
@stop
