@extends('layouts.adminlte')

@section('page_title', 'Surgical Complications Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('By Severity') }}</h3></div>
        <div class="card-body">@forelse($complications as $severity => $total)<div>{{ ucfirst($severity) }}: {{ $total }}</div>@empty<div>{{ __('No data.') }}</div>@endforelse</div>
    </div>
@stop
