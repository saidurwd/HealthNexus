@extends('layouts.adminlte')

@section('page_title', 'Inpatient History — '.$patient->full_name)

@section('page_content')
    <h3>{{ $patient->full_name }} — Inpatient History</h3>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Admissions</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>Admission #</th><th>Type</th><th>Location</th><th>Attending</th><th>Admitted</th><th>Discharged</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($admissions as $admission)
                        <tr>
                            <td>{{ $admission->admission_number }}</td>
                            <td>{{ $admission->admissionType?->name ?? '—' }}</td>
                            <td>
                                @if($admission->currentAllocation?->bed)
                                    {{ $admission->currentAllocation->bed->room?->ward?->name }} / {{ $admission->currentAllocation->bed->bed_code }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $admission->attendingProvider?->name ?? '—' }}</td>
                            <td>{{ $admission->admitted_at?->format('Y-m-d') }}</td>
                            <td>{{ $admission->actual_discharge_date?->format('Y-m-d') ?? '—' }}</td>
                            <td><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $admission->status)) }}</span></td>
                            <td>
                                @can('ipd.admission.view')
                                    <a href="{{ route('admin.ipd.admissions.show', $admission) }}" class="btn btn-xs btn-info">View</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">No admissions for this patient.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
