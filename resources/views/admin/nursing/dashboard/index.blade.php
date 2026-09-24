@extends('layouts.adminlte')

@section('page_title', 'Nursing Dashboard')

@section('page_content')
    <div class="row">
        @foreach($metrics as $label => $value)
            <div class="col-md-3 col-sm-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h3>{{ $value }}</h3>
                        <div class="text-muted">{{ ucwords(str_replace('_', ' ', $label)) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">My Patients</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Patient</th><th>Admission #</th><th>Ward / Bed</th><th></th></tr></thead>
                <tbody>
                    @forelse($myPatients as $episode)
                        <tr>
                            <td>{{ $episode->patient?->full_name }}</td>
                            <td>{{ $episode->admission?->admission_number }}</td>
                            <td>{{ $episode->admission?->currentAllocation?->bed?->room?->ward?->name ?? '—' }} / {{ $episode->admission?->currentAllocation?->bed?->bed_code ?? '—' }}</td>
                            <td><a href="{{ route('admin.nursing.episodes.show', $episode) }}" class="btn btn-xs btn-info">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No patients currently assigned to you.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
