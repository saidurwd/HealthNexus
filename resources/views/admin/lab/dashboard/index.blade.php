@extends('layouts.adminlte')

@section('page_title', __('Laboratory'))

@section('page_content')
    @php $L = fn ($route, $perm, $q = []) => \App\Support\Dashboard\Link::to($route, $perm, $q); $N = fn ($v) => \App\Support\Dashboard\Numbers::number($v); $c = $metrics['current']; $p = $metrics['previous']; @endphp
    <x-ui.dashboard :question="__('Are the results getting to the doctors fast enough, and is anything critical left unseen?')" :audience="__('For the laboratory head and the pathologists')" :period="$period" :periods="['today', '7d', '30d', '90d', 'month', 'last_month', 'custom']" :as-of="$asOf">
        <div class="row row-cols-2 row-cols-md-4 row-cols-xl-6 g-2 mb-3">
            <x-ui.kpi-tile :label="__('Tests to work on')" :value="$metrics['to_work']" unit="count" icon="bi-clipboard2-pulse" variant="primary" :href="$L('admin.lab.worklist.index', 'lab.result.view')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Waiting to be checked')" :value="$metrics['awaiting_validation']" unit="count" icon="bi-patch-check" variant="warning" :href="$L('admin.lab.orders.index', 'lab.order.view', ['status' => 'awaiting_validation'])" good-direction="down" />
            <x-ui.kpi-tile :label="__('Samples not yet in')" :value="$metrics['specimens_pending']" unit="count" icon="bi-droplet" variant="info" :href="$L('admin.lab.specimens.index', 'lab.specimen.view')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Critical results unseen')" :value="$metrics['critical_unacknowledged']" unit="count" icon="bi-exclamation-triangle" variant="danger" :href="$L('admin.lab.critical-results.index', 'lab.critical_result.view')" good-direction="down" :note="$metrics['oldest_critical_minutes'] !== null ? __('oldest :n minutes', ['n' => $N($metrics['oldest_critical_minutes'])]) : null" />
            <x-ui.kpi-tile :label="__('Reported today')" :value="$metrics['reported_today']" unit="count" icon="bi-file-earmark-check" variant="success" good-direction="up" :note="__('usually :n on this weekday', ['n' => $N($metrics['reported_usual'])])" />
            <x-ui.kpi-tile :label="__('Orders in the period')" :value="$c['orders']" :previous="$p['orders']" unit="count" icon="bi-journal-medical" variant="primary" good-direction="none" />
            <x-ui.kpi-tile :label="__('Typical time to result')" :value="$c['tat']['median'] ?? null" :previous="$p['tat']['median'] ?? null" unit="minutes" icon="bi-stopwatch" variant="info" good-direction="down" :target="app(\App\Services\Dashboard\Targets::class)->get('lab_tat')" :note="$c['tat'] ? __('9 in 10 within :m min · :n results', ['m' => $N($c['tat']['p90']), 'n' => $N($c['tat']['n'])]) : __('No results in this period.')" />
            <x-ui.kpi-tile :label="__('Samples rejected')" :value="$c['rejected_percent']" :previous="$p['rejected_percent']" unit="percent" icon="bi-slash-circle" variant="secondary" good-direction="down" />
        </div>
        <div class="row g-3">
            <div class="col-lg-5"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Where the open tests are') }}</h3></div><div class="card-body">
                <x-ui.chart kind="hbar" :rows="$metrics['stages']" x="stage" :series="[['key' => 'n', 'label' => 'Tests']]" />
            </div></div></div>
            <div class="col-lg-7"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Orders per day') }} <span class="small text-muted">· {{ $period->label() }}</span></h3></div><div class="card-body">
                <x-ui.chart kind="bar" :rows="$metrics['per_day']" x="day" :series="[['key' => 'orders', 'label' => 'Orders']]" />
            </div></div></div>
        </div>
    </x-ui.dashboard>
@stop
