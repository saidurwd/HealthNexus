@extends('layouts.adminlte')

@section('page_title', 'Inpatient History — '.$patient->full_name)

@section('page_content')
    <h3>{{ $patient->full_name }} — Inpatient History</h3>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Admissions') }}</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>{{ __('Admission #') }}</th><th>{{ __('Type') }}</th><th>{{ __('Location') }}</th><th>{{ __('Attending') }}</th><th>{{ __('Admitted') }}</th><th>{{ __('Discharged') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
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
                            <td><x-ui.status-badge :status="$admission->status" /></td>
                            <td>
                                @can('ipd.admission.view')
                                    <a href="{{ route('admin.ipd.admissions.show', $admission) }}" class="btn btn-xs btn-info">{{ __('View') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">{{ __('No admissions for this patient.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
