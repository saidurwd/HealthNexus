@extends('layouts.adminlte')

@section('page_title', 'ICU Discharge Summary')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $summary->episode->patient?->full_name ?? '' }}</h3>
            <span class="badge bg-info float-right">{{ $summary->status }}</span>
        </div>
        <div class="card-body">
            <p><strong>Destination:</strong> {{ $summary->destination }}</p>
            <p><strong>Diagnoses:</strong> {{ $summary->diagnoses }}</p>
            <p><strong>Major Events:</strong> {{ $summary->major_events }}</p>
            <p><strong>Current Status:</strong> {{ $summary->current_status }}</p>
            <p><strong>Follow-up Plan:</strong> {{ $summary->follow_up_plan }}</p>

            @if($summary->status === 'draft')
                <form method="POST" action="{{ route('admin.icu.discharge.finalize', $summary) }}">
                    @csrf
                    <button type="submit" class="btn btn-danger">Finalize Discharge</button>
                </form>
            @endif
        </div>
    </div>
@stop
