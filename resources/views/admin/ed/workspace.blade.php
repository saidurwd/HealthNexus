@extends('layouts.adminlte')

@section('page_title', __('My emergency workspace'))

@section('page_content')
    <div class="row g-3 mb-3">
        @foreach([[__('Waiting for triage'), $waitingTriage], [__('Triaged, not yet seen by a doctor'), $unseen], [__('My overdue protocol steps'), $overdueSteps], [__('My open consultations'), $openConsults]] as [$label, $value])
            <div class="col-6 col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ $label }}</div><div class="fs-3 fw-semibold {{ $value > 0 ? 'text-danger' : '' }}">{{ $value }}</div></div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('My patients') }}</h3></div>
        <div class="table-responsive"><table class="table table-sm table-hover mb-0">
            <thead><tr><th>{{ __('Level') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Status') }}</th><th>{{ __('Bay') }}</th><th>{{ __('Arrived') }}</th></tr></thead>
            <tbody>
            @forelse($mine as $visit)
                <tr><td>{{ $visit->triage_level ?? '—' }}</td><td><a href="{{ route('admin.ed.visits.show', $visit) }}">{{ $visit->patient->full_name }}</a> <small class="text-muted">{{ $visit->visit_number }}</small></td>
                    <td><x-ui.status-badge :status="$visit->status" /></td><td>{{ $visit->bay?->code ?? '—' }}</td><td>{{ $visit->arrival_at->format('H:i') }}</td></tr>
            @empty
                <tr><td colspan="5" class="text-muted">{{ __('No patients are assigned to you.') }}</td></tr>
            @endforelse
            </tbody></table></div>
    </div>
@stop
