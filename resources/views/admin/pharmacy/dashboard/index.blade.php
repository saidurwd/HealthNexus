@extends('layouts.adminlte')

@section('page_title', __('Pharmacy'))

@section('page_content')
    @php $L = fn ($route, $perm, $q = []) => \App\Support\Dashboard\Link::to($route, $perm, $q); $N = fn ($v) => \App\Support\Dashboard\Numbers::number($v); $c = $metrics['current']; $p = $metrics['previous']; @endphp
    <x-ui.dashboard :question="__('Are prescriptions being filled quickly and safely, and is the shelf in good order?')" :audience="__('For the chief pharmacist')" :period="$period" :periods="['today', '7d', '30d', '90d', 'month', 'last_month', 'custom']" :as-of="$asOf">
        <div class="row row-cols-2 row-cols-md-4 row-cols-xl-5 g-2 mb-3">
            <x-ui.kpi-tile :label="__('Prescriptions waiting')" :value="$metrics['orders_pending']" unit="count" icon="bi-capsule" variant="primary" :href="$L('admin.pharmacy.orders.index', 'pharmacy.prescription.view', ['status' => 'pending'])" good-direction="down" />
            <x-ui.kpi-tile :label="__('Under review')" :value="$metrics['orders_under_review']" unit="count" icon="bi-hourglass-split" variant="info" :href="$L('admin.pharmacy.orders.index', 'pharmacy.prescription.view', ['status' => 'under_review'])" good-direction="down" />
            <x-ui.kpi-tile :label="__('Safety alerts open')" :value="$metrics['unresolved_safety_alerts']" unit="count" icon="bi-shield-exclamation" variant="danger" :href="$L('admin.pharmacy.safety-alerts.index', 'pharmacy.safety_alert.view')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Medicines out of stock')" :value="$metrics['stock_outs']" unit="count" icon="bi-box-seam" variant="danger" :href="$L('admin.pharmacy.stock.index', 'pharmacy.stock.view')" good-direction="down" :note="__('nothing usable on the shelf')" />
            <x-ui.kpi-tile :label="__('Batches near expiry')" :value="$metrics['near_expiry_batches']" unit="count" icon="bi-calendar-x" variant="warning" :href="$L('admin.pharmacy.stock.index', 'pharmacy.stock.view')" good-direction="down" :note="__('within :n days', ['n' => $N($metrics['near_days'])])" />
            <x-ui.kpi-tile :label="__('Batches set aside')" :value="$metrics['quarantined_batches']" unit="count" icon="bi-slash-circle" variant="secondary" :href="$L('admin.pharmacy.quarantine.index', 'pharmacy.quarantine.manage')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Dispensed today')" :value="$metrics['dispensed_today']" unit="count" icon="bi-bag-check" variant="success" good-direction="up" :note="__('usually :n on this weekday', ['n' => $N($metrics['dispensed_usual'])])" />
            <x-ui.kpi-tile :label="__('Dispensed in the period')" :value="$c['dispensed']" :previous="$p['dispensed']" unit="count" icon="bi-bag" variant="primary" good-direction="none" />
            <x-ui.kpi-tile :label="__('Typical wait for medicines')" :value="$c['wait']['median'] ?? null" :previous="$p['wait']['median'] ?? null" unit="minutes" icon="bi-stopwatch" variant="info" good-direction="down" :target="app(\App\Services\Dashboard\Targets::class)->get('pharmacy_wait')" :note="$c['wait'] ? __('9 in 10 within :m min · :n orders', ['m' => $N($c['wait']['p90']), 'n' => $N($c['wait']['n'])]) : __('Nothing was dispensed in this period.')" />
        </div>
        <div class="row g-3">
            <div class="col-lg-8"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Dispensed per day') }} <span class="small text-muted">· {{ $period->label() }}</span></h3></div><div class="card-body">
                <x-ui.chart kind="bar" :rows="$metrics['per_day']" x="day" :series="[['key' => 'dispensed', 'label' => 'Dispensed']]" />
            </div></div></div>
            <div class="col-lg-4"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Safety alerts by seriousness') }}</h3></div><div class="card-body">
                <x-ui.chart kind="hbar" :rows="$metrics['alerts_by_severity']" x="severity" :series="[['key' => 'n', 'label' => 'Alerts']]" />
            </div></div></div>
        </div>
    </x-ui.dashboard>
@stop
