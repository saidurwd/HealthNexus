@extends('layouts.adminlte')

@section('page_title', __('Patient welfare'))

@section('page_content')
    @include('admin.welfare._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <p class="text-muted small">{{ __('Money given to the hospital for patients who cannot pay, and the decisions that spend it. A person is assessed, a committee approves a grant, and Billing takes the amount off the bill.') }}</p>
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3"><div class="card card-body py-2"><div class="small text-muted">{{ __('Free to give now') }}</div><div class="fs-5 fw-semibold">৳ {{ number_format((float) $s['total_available'], 2) }}</div></div></div>
        <div class="col-6 col-md-3"><div class="card card-body py-2"><div class="small text-muted">{{ __('Given in :n days', ['n' => $s['days']]) }}</div><div class="fs-5 fw-semibold">৳ {{ number_format((float) $s['given'], 2) }}</div><div class="small text-muted">{{ __(':n patients helped', ['n' => $s['patients_helped']]) }}</div></div></div>
        <div class="col-6 col-md-3"><div class="card card-body py-2"><div class="small text-muted">{{ __('Received in :n days', ['n' => $s['days']]) }}</div><div class="fs-5 fw-semibold">৳ {{ number_format((float) $s['received'], 2) }}</div></div></div>
        <div class="col-6 col-md-3"><div class="card card-body py-2"><div class="small text-muted">{{ __('Waiting for a decision') }}</div><div class="fs-5 fw-semibold">{{ $s['assessments_waiting'] + $s['grants_waiting'] }}</div><div class="small text-muted">{{ __(':a assessments · :g grants · :b with Billing', ['a' => $s['assessments_waiting'], 'g' => $s['grants_waiting'], 'b' => $s['with_billing']]) }}</div></div></div>
    </div>
    <div class="d-flex flex-wrap gap-2 mb-3">
        @if($can['assess'])<a class="btn btn-outline-primary btn-sm" href="{{ route('admin.welfare.assessments') }}"><i class="bi bi-clipboard2-check"></i> {{ __('Assessments') }}</a>@endif
        @if($can['grant'])<a class="btn btn-outline-primary btn-sm" href="{{ route('admin.welfare.grants') }}"><i class="bi bi-gift"></i> {{ __('Grants') }}</a>@endif
        @if($can['fund'])<a class="btn btn-outline-primary btn-sm" href="{{ route('admin.welfare.funds') }}"><i class="bi bi-piggy-bank"></i> {{ __('Funds') }}</a>@endif
        @can('welfare.report.view')<a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.welfare.reports') }}"><i class="bi bi-bar-chart"></i> {{ __('Reports') }}</a>@endcan
    </div>
    <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Funds') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Fund') }}</th><th>{{ __('Kind') }}</th><th class="text-end">{{ __('Balance') }}</th><th class="text-end">{{ __('Set aside for grants') }}</th><th class="text-end">{{ __('Free to give') }}</th></tr></thead><tbody>
        @forelse($s['funds'] as $r)<tr><td>@if($can['fund'])<a href="{{ route('admin.welfare.funds.show', $r['fund']->public_id) }}">{{ $r['fund']->name }}</a>@else{{ $r['fund']->name }}@endif @if($r['fund']->status === 'closed')<x-ui.status-badge status="closed" :label="__('Closed')" />@endif</td><td>{{ $H($r['fund']->kind) }}</td><td class="text-end">{{ number_format((float) $r['balance'], 2) }}</td><td class="text-end">{{ number_format((float) $r['committed'], 2) }}</td><td class="text-end fw-semibold">{{ number_format((float) $r['available'], 2) }}</td></tr>@empty<tr><td colspan="5" class="text-muted">{{ __('No funds yet.') }}</td></tr>@endforelse</tbody></table></div></div>
    <div class="row g-3">
        @if($waiting->isNotEmpty())<div class="col-md-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Assessments waiting for the committee') }}</h3></div><ul class="list-group list-group-flush small">@foreach($waiting as $a)<li class="list-group-item d-flex justify-content-between"><a href="{{ route('admin.welfare.assessments.show', $a->public_id) }}">{{ $a->number }} · {{ $a->patient?->display_name }}</a><span class="text-muted">{{ $a->updated_at?->diffForHumans() }}</span></li>@endforeach</ul></div></div>@endif
        @if($billing->isNotEmpty())<div class="col-md-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Waiting for Billing to approve') }}</h3></div><ul class="list-group list-group-flush small">@foreach($billing as $b)<li class="list-group-item d-flex justify-content-between"><span>{{ $b->grant?->number }} · {{ $b->invoice?->invoice_number }}</span><span>৳ {{ number_format((float) $b->amount, 2) }}</span></li>@endforeach</ul></div></div>@endif
    </div>
@stop
