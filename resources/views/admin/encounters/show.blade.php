@extends('adminlte::page')

@section('title', 'Encounter Details')

@section('content_header')
    <h1>Encounter #{{ $encounter->id }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <p><strong>Patient:</strong> {{ $encounter->patient->full_name ?? 'N/A' }}</p>
            <p><strong>Type:</strong> {{ $encounter->encounter_type }}</p>
            <p><strong>Status:</strong> {{ $encounter->status }}</p>
            <p><strong>Started At:</strong> {{ $encounter->started_at?->format('Y-m-d H:i') }}</p>
            <p><strong>Ended At:</strong> {{ $encounter->ended_at?->format('Y-m-d H:i') }}</p>
            <p><strong>Notes:</strong> {{ $encounter->notes }}</p>
            <a href="{{ route('admin.encounters.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>
@stop
