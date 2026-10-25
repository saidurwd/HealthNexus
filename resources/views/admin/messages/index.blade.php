@extends('layouts.adminlte')

@section('page_title', 'Messages')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Messages') }}</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                {{ __('No messages available.') }}
            </div>
        </div>
    </div>
@stop
