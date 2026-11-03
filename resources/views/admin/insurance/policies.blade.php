@extends('layouts.adminlte')

@section('page_title', __('Patient policies'))

@section('page_content')
    @include('admin.insurance._flash')
    <form method="GET" class="d-flex gap-2 mb-3"><input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="{{ __('Patient, policy or member number') }}"><button class="btn btn-outline-secondary">{{ __('Search') }}</button></form>
    @can('insurance.policy.manage')
    <form method="POST" action="{{ route('admin.insurance.policies.store') }}" class="card mb-3">@csrf
        <div class="card-header"><h3 class="card-title">{{ __('Add a policy') }}</h3></div>
        <div class="card-body row g-2">
            <div class="col-md-2"><input name="patient" class="form-control" placeholder="{{ __('Patient number') }}" required></div>
            <div class="col-md-3"><select name="plan" class="form-select" required><option value="">{{ __('Plan') }}</option>@foreach($payers as $p)<optgroup label="{{ $p->name }}">@foreach($p->plans as $plan)<option value="{{ $plan->public_id }}">{{ $plan->name }}</option>@endforeach</optgroup>@endforeach</select></div>
            <div class="col-md-2"><input name="policy_number" class="form-control" placeholder="{{ __('Policy number') }}" required></div><div class="col-md-2"><input name="member_number" class="form-control" placeholder="{{ __('Member number') }}" required></div>
            <div class="col-md-1"><input type="date" name="effective_from" class="form-control" required></div><div class="col-md-1"><input type="date" name="effective_to" class="form-control"></div>
            <div class="col-md-1"><select name="coverage_order" class="form-select"><option value="1">1</option><option value="2">2</option></select></div>
            <div class="col-12"><button class="btn btn-danger">{{ __('Add policy') }}</button></div>
        </div></form>
    @endcan
    <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0">
        <thead><tr><th>{{ __('Patient') }}</th><th>{{ __('Payer / plan') }}</th><th>{{ __('Policy') }}</th><th>{{ __('Valid') }}</th><th>{{ __('Used this year') }}</th><th>{{ __('Eligibility') }}</th></tr></thead><tbody>
        @forelse($policies as $pol)
            @php($patient = $patients[$pol->patient_id] ?? null)@php($check = $latest[$pol->id] ?? null)
            <tr><td>{{ $patient?->first_name }} {{ $patient?->last_name }} <small class="text-muted">{{ $patient?->enterprise_patient_no }}</small></td><td>{{ $payerNames[$pol->payer_id] ?? '—' }} / {{ $planNames[$pol->plan_id] ?? '—' }} @if($pol->coverage_order > 1)<span class="badge text-bg-secondary">{{ __('second') }}</span>@endif</td>
                <td>{{ $pol->policy_number }} <small class="text-muted">{{ $pol->member_number }}</small></td><td>{{ $pol->effective_from->format('d M Y') }} – {{ $pol->effective_to?->format('d M Y') ?? '…' }}</td><td>{{ $pol->annual_used }}</td>
                <td>@if($check)<x-ui.status-badge :status="$check->status" /> <small class="text-muted">{{ $check->checked_at->format('d M H:i') }}</small>@else — @endif
                    @can('insurance.eligibility.verify')<form method="POST" action="{{ route('admin.insurance.policies.verify', $pol) }}" class="d-inline-flex gap-1 mt-1">@csrf<select name="status" class="form-select form-select-sm"><option value="eligible">{{ __('Eligible') }}</option><option value="not_eligible">{{ __('Not eligible') }}</option><option value="adapter">{{ __('Ask the payer system') }}</option></select><button class="btn btn-sm btn-outline-secondary">{{ __('Check') }}</button></form>@endcan</td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('No policies found.') }}</td></tr>@endforelse
        </tbody></table></div><div class="card-footer">{{ $policies->links() }}</div></div>
@stop
