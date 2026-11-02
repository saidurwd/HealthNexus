@extends('layouts.adminlte')

@section('page_title', __('Patient welfare'))

@section('page_content')
    @include('admin.welfare._flash')
    @php
        $H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v));
        $c = $s['current']; $p = $s['previous'];
        $q = $period->query();
        $spark = collect($s['months'])->pluck('given')->all();
    @endphp
    <x-ui.dashboard :question="__('Is there enough free money to help the patients who are waiting, and where is it going?')" :audience="__('For the welfare officer, the committee and the hospital director')" :period="$period" :periods="['7d', '30d', '90d', 'month', 'last_month', 'custom']" :as-of="$asOf">
        <div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-2 mb-3">
            <x-ui.kpi-tile :label="__('Free to give now')" :value="$s['total_available']" unit="taka" icon="bi-piggy-bank" variant="success" :href="$can['fund'] ? route('admin.welfare.funds') : null" :note="__('after what is set aside')" />
            <x-ui.kpi-tile :label="__('Given to patients')" :value="$c['given']" :previous="$p['given']" unit="taka" icon="bi-gift" variant="primary" :href="$can['grant'] ? route('admin.welfare.discounts', $q) : null" :spark="$spark" good-direction="none" />
            <x-ui.kpi-tile :label="__('Patients helped')" :value="$c['patients']" :previous="$p['patients']" unit="count" icon="bi-people" variant="primary" :href="$can['grant'] ? route('admin.welfare.discounts', $q) : null" good-direction="none" />
            <x-ui.kpi-tile :label="__('Received')" :value="$c['received']" :previous="$p['received']" unit="taka" icon="bi-box-arrow-in-down" variant="info" :href="$can['fund'] ? route('admin.welfare.gifts', $q) : null" />
            <x-ui.kpi-tile :label="__('Assessments waiting')" :value="$s['assessments_waiting']" unit="count" icon="bi-clipboard2-check" variant="warning" :href="$can['assess'] ? route('admin.welfare.assessments', ['status' => 'submitted']) : null" good-direction="down" />
            <x-ui.kpi-tile :label="__('Waiting for Billing')" :value="$s['with_billing']" unit="count" icon="bi-hourglass-split" variant="warning" :href="$can['grant'] ? route('admin.welfare.discounts', ['status' => 'requested']) : null" good-direction="down" />
        </div>
        <div class="row g-3 mb-3">
            <div class="col-lg-7"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Received and given, last 6 months') }}</h3></div><div class="card-body">
                <x-ui.chart kind="bar" :rows="$s['months']" x="month" :series="[['key' => 'received', 'label' => 'Received'], ['key' => 'given', 'label' => 'Given']]" unit="taka" />
            </div></div></div>
            <div class="col-lg-5"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Given by fund') }} <span class="small text-muted">· {{ $period->label() }}</span></h3></div><div class="card-body">
                @if(count($s['by_fund']) > 0)<x-ui.chart kind="hbar" :rows="$s['by_fund']" x="fund" :series="[['key' => 'given', 'label' => 'Given']]" unit="taka" />
                @else<x-ui.empty-state :title="__('Nothing was given in this period.')" :hint="__('Choose a longer period, or ask Billing to approve the waiting discounts.')" icon="bi-gift" />@endif
            </div></div></div>
        </div>
        <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Funds') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Fund') }}</th><th>{{ __('Kind') }}</th><th class="text-end">{{ __('Balance') }}</th><th class="text-end">{{ __('Set aside for grants') }}</th><th class="text-end">{{ __('Free to give') }}</th></tr></thead><tbody>
            @forelse($s['funds'] as $f)<tr><td>@if($can['fund'])<a href="{{ route('admin.welfare.funds.show', $f['public_id']) }}">{{ $f['name'] }}</a>@else{{ $f['name'] }}@endif @if($f['status'] === 'closed')<x-ui.status-badge status="closed" :label="__('Closed')" />@endif</td><td>{{ $H($f['kind']) }}</td><td class="text-end">{{ \App\Support\Dashboard\Numbers::number($f['balance'], 2) }}</td><td class="text-end">{{ \App\Support\Dashboard\Numbers::number($f['committed'], 2) }}</td><td class="text-end fw-semibold">{{ \App\Support\Dashboard\Numbers::number($f['available'], 2) }}</td></tr>@empty<tr><td colspan="5"><x-ui.empty-state :title="__('No funds yet.')" :hint="__('Add a fund, then record the money received.')" /></td></tr>@endforelse</tbody></table></div></div>
        <div class="row g-3">
            @if($waiting->isNotEmpty())<div class="col-md-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Assessments waiting for the committee') }}</h3></div><ul class="list-group list-group-flush small">@foreach($waiting as $a)<li class="list-group-item d-flex justify-content-between"><a href="{{ route('admin.welfare.assessments.show', $a->public_id) }}">{{ $a->number }} · {{ $a->patient?->display_name }}</a><span class="text-muted">{{ $a->updated_at?->diffForHumans() }}</span></li>@endforeach</ul></div></div>@endif
            @if($billing->isNotEmpty())<div class="col-md-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Waiting for Billing to approve') }}</h3></div><ul class="list-group list-group-flush small">@foreach($billing as $b)<li class="list-group-item d-flex justify-content-between"><span>{{ $b->grant?->number }} · {{ $b->invoice?->invoice_number }}</span><span>৳ {{ \App\Support\Dashboard\Numbers::number($b->amount, 2) }}</span></li>@endforeach</ul></div></div>@endif
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3">
            @if($can['assess'])<a class="btn btn-outline-primary btn-sm" href="{{ route('admin.welfare.assessments') }}"><i class="bi bi-clipboard2-check"></i> {{ __('Assessments') }}</a>@endif
            @if($can['grant'])<a class="btn btn-outline-primary btn-sm" href="{{ route('admin.welfare.grants') }}"><i class="bi bi-gift"></i> {{ __('Grants') }}</a>@endif
            @if($can['fund'])<a class="btn btn-outline-primary btn-sm" href="{{ route('admin.welfare.funds') }}"><i class="bi bi-piggy-bank"></i> {{ __('Funds') }}</a>@endif
            @can('welfare.report.view')<a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.welfare.reports') }}"><i class="bi bi-bar-chart"></i> {{ __('Reports') }}</a>@endcan
        </div>
    </x-ui.dashboard>
@stop
