@extends('layouts.adminlte')

@section('page_title', 'Encounter Details')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Encounter #{{ $encounter->id }}</h3>
            <div class="card-tools">
                <a href="{{ route('admin.encounters.edit', $encounter) }}" class="btn btn-warning btn-sm">Edit</a>
                <a href="{{ route('admin.encounters.index') }}" class="btn btn-secondary btn-sm">Back</a>
            </div>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Patient</dt>
                <dd class="col-sm-9">{{ $encounter->patient->full_name ?? 'N/A' }}</dd>

                <dt class="col-sm-3">Type</dt>
                <dd class="col-sm-9">{{ $encounter->encounter_type }}</dd>

                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">{{ $encounter->status }}</dd>

                <dt class="col-sm-3">Started At</dt>
                <dd class="col-sm-9">{{ $encounter->started_at?->format('Y-m-d H:i') }}</dd>

                <dt class="col-sm-3">Ended At</dt>
                <dd class="col-sm-9">{{ $encounter->ended_at?->format('Y-m-d H:i') }}</dd>

                <dt class="col-sm-3">Notes</dt>
                <dd class="col-sm-9">{{ $encounter->notes }}</dd>
            </dl>
        </div>
    </div>
@stop
