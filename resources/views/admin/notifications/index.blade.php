@extends('layouts.adminlte')

@section('page_title', 'Notifications')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Notifications') }}</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                {{ __('No new notifications.') }}
            </div>
        </div>
    </div>
@stop
