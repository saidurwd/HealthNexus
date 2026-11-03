@extends('layouts.adminlte')

@section('page_title', 'Surgery Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('By Status') }}</h3></div>
        <div class="card-body">@forelse($byStatus as $status => $total)<div>{{ ucwords(str_replace('_',' ',$status)) }}: {{ $total }}</div>@empty<div>{{ __('No data.') }}</div>@endforelse</div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('By Specialty') }}</h3></div>
        <div class="card-body">@forelse($bySpecialty as $name => $total)<div>{{ $name }}: {{ $total }}</div>@empty<div>{{ __('No data.') }}</div>@endforelse</div>
    </div>
@stop
