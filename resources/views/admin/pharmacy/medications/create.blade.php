@extends('layouts.adminlte')

@section('page_title', 'New Medication')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('New Medication') }}</h3></div>
        <form action="{{ route('admin.pharmacy.medications.store') }}" method="post">
            @csrf
            @include('admin.pharmacy.medications._form', ['medication' => null])
            <div class="card-footer">
                <a href="{{ route('admin.pharmacy.medications.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('Save Medication') }}</button>
            </div>
        </form>
    </div>
@stop
