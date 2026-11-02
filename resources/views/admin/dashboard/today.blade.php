@extends('layouts.adminlte')

@section('page_title', __('Today in the hospital'))

@section('page_content')
    @php
        $o = $s['opd']; $e = $s['ed']; $i = $s['ipd']; $t = $s['theatre']; $b = $s['billed']; $c = $s['collected'];
        $usual = __('the usual :day', ['day' => \Carbon\CarbonImmutable::parse($s['date'])->translatedFormat('l')]);
    @endphp
    <x-ui.dashboard :question="__('Is today going well?')" :audience="__('For the hospital director. Counts and money only, no patients.')" :as-of="$asOf" :refresh="config('ux.dashboard.refresh_seconds.management')">
        <p class="small text-muted mb-2">{{ \App\Support\Dashboard\Numbers::digits(\Carbon\CarbonImmutable::parse($s['date'])->translatedFormat('l, d F Y')) }} · {{ __('Compared with the average of the same weekday over the last four weeks.') }}</p>
        <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-2 mb-3">
            <x-ui.kpi-tile :label="__('Outpatients today')" :value="$o['today']" :previous="$o['usual']" unit="count" icon="bi-person-walking" variant="primary" :href="$can['opd'] ? route('admin.encounters.index') : null" :spark="$o['trend']" :compare-label="$usual" good-direction="none" />
            <x-ui.kpi-tile :label="__('Emergency arrivals today')" :value="$e['today']" :previous="$e['usual']" unit="count" icon="bi-hospital-fill" variant="danger" :href="$can['ed'] ? route('admin.ed.board') : null" :spark="$e['trend']" :compare-label="$usual" good-direction="none" :note="__(':n in the emergency department now', ['n' => \App\Support\Dashboard\Numbers::number($e['in_ed_now'])])" />
            <x-ui.kpi-tile :label="__('Inpatients now')" :value="$i['inpatients']" unit="count" icon="bi-hospital" variant="info" :href="$can['ipd'] ? route('admin.ipd.dashboard') : null" good-direction="none" :note="__(':a admitted · :d discharged today', ['a' => \App\Support\Dashboard\Numbers::number($i['admitted_today']), 'd' => \App\Support\Dashboard\Numbers::number($i['discharged_today'])])" />
            <x-ui.kpi-tile :label="__('Bed occupancy')" :value="$i['occupancy']" unit="percent" icon="bi-grid-3x3-gap" variant="info" :href="$can['ipd'] ? route('admin.ipd.dashboard') : null" good-direction="none" :note="$i['occupancy'] === null ? __('No beds are set up.') : __(':o of :b beds in use', ['o' => \App\Support\Dashboard\Numbers::number($i['occupied']), 'b' => \App\Support\Dashboard\Numbers::number($i['beds'])])" />
            <x-ui.kpi-tile :label="__('Theatre cases today')" :value="$t['cases_today']" unit="count" icon="bi-scissors" variant="secondary" :href="$can['ot'] ? route('admin.ot.dashboard') : null" good-direction="none" :note="$t['called_off_today'] > 0 ? __(':n called off or postponed', ['n' => \App\Support\Dashboard\Numbers::number($t['called_off_today'])]) : null" />
            <x-ui.kpi-tile :label="__('Billed today')" :value="$b['today']" :previous="$b['usual']" unit="taka" icon="bi-receipt" variant="success" :href="$can['billing'] ? route('admin.billing.dashboard') : null" :spark="$b['trend']" :compare-label="$usual" />
            <x-ui.kpi-tile :label="__('Collected today')" :value="$c['today']" :previous="$c['usual']" unit="taka" icon="bi-cash-coin" variant="success" :href="$can['billing'] ? route('admin.billing.dashboard') : null" :spark="$c['trend']" :compare-label="$usual" />
            <x-ui.kpi-tile :label="__('Alerts waiting to be acknowledged')" :value="$s['alerts_open']" unit="count" icon="bi-bell" variant="danger" good-direction="down" :note="__(':n of them critical', ['n' => \App\Support\Dashboard\Numbers::number($s['critical_open'])])" />
        </div>
        <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Waiting for a decision') }}</h3></div><ul class="list-group list-group-flush small">
            <li class="list-group-item d-flex justify-content-between"><span>@if($can['adjust'])<a href="{{ route('admin.billing.adjustments.index') }}">{{ __('Discounts waiting for approval') }}</a>@else{{ __('Discounts waiting for approval') }}@endif</span><strong>{{ \App\Support\Dashboard\Numbers::number($s['approvals']['discounts']) }}</strong></li>
            <li class="list-group-item d-flex justify-content-between"><span>@if($can['welfare'])<a href="{{ route('admin.welfare.grants') }}">{{ __('Welfare grants waiting for approval') }}</a>@else{{ __('Welfare grants waiting for approval') }}@endif</span><strong>{{ \App\Support\Dashboard\Numbers::number($s['approvals']['grants']) }}</strong></li>
        </ul></div>
    </x-ui.dashboard>
@stop
