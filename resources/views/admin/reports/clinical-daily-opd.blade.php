@extends('adminlte::page')

@section('title', 'Daily OPD Report')

@section('content_header')
    <h1>Daily OPD Report</h1>
@stop

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="form-inline">
                <label class="mr-2">Date</label>
                <input type="date" name="date" value="{{ $date }}" class="form-control mr-3">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.reports.clinical') }}" class="btn btn-outline-secondary ml-2">Back to Clinical Reports</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Encounters on {{ $date }}</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Encounter No</th>
                        <th>Patient</th>
                        <th>Provider</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($encounters as $encounter)
                        <tr>
                            <td><a href="{{ route('admin.encounters.show', $encounter) }}">{{ $encounter->encounter_no }}</a></td>
                            <td>{{ $encounter->patient?->full_name ?? $encounter->patient_id }}</td>
                            <td>{{ $encounter->provider?->name ?? '—' }}</td>
                            <td>{{ $encounter->encounterType?->name ?? $encounter->encounter_type }}</td>
                            <td>{{ ucfirst($encounter->status) }}</td>
                            <td>{{ $encounter->created_at->format('H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No encounters for this date.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $encounters->links() }}
        </div>
    </div>
@stop
