@extends('layouts.adminlte')

@section('page_title', 'Medication Administration Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Administration by Status</h3></div>
        <div class="card-body">
            @forelse($stats as $status => $total)<div>{{ ucfirst($status) }}: {{ $total }}</div>@empty<div>No data.</div>@endforelse
        </div>
    </div>
@stop
