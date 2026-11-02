@extends('layouts.adminlte')

@section('page_title', __('Billing'))

@section('page_content')
    @php $L = fn ($route, $perm, $q = []) => \App\Support\Dashboard\Link::to($route, $perm, $q); $N = fn ($v) => \App\Support\Dashboard\Numbers::number($v); $c = $metrics['current']; $p = $metrics['previous']; $q = $period->query(); @endphp
    <x-ui.dashboard :question="__('How much was billed and collected, and how much is still owed?')" :audience="__('For the billing manager and the director')" :period="$period" :periods="['today', '7d', '30d', '90d', 'month', 'last_month', 'custom']" :as-of="$asOf">
        <div class="row row-cols-2 row-cols-md-4 row-cols-xl-5 g-2 mb-3">
            <x-ui.kpi-tile :label="__('Billed')" :value="$c['billed']" :previous="$p['billed']" unit="taka" icon="bi-receipt" variant="primary" :href="$L('admin.billing.invoices.index', 'billing.invoice.view')" good-direction="up" :note="__(':n bills', ['n' => $N($c['invoices'])])" />
            <x-ui.kpi-tile :label="__('Collected')" :value="$c['collected']" :previous="$p['collected']" unit="taka" icon="bi-cash-coin" variant="success" :href="$L('admin.billing.payments.index', 'billing.payment.view')" good-direction="up" :note="__(':n payments', ['n' => $N($c['payments'])])" />
            <x-ui.kpi-tile :label="__('Collected, as a share of billed')" :value="$c['collection_rate']" :previous="$p['collection_rate']" unit="percent" icon="bi-percent" variant="info" good-direction="up" :target="app(\App\Services\Dashboard\Targets::class)->get('collection_rate')" :note="__('can pass 100 when old bills are paid')" />
            <x-ui.kpi-tile :label="__('Still owed')" :value="$metrics['owed']" unit="taka" icon="bi-exclamation-circle" variant="warning" :href="$L('admin.billing.reports.receivables', 'billing.report.view')" good-direction="down" :note="__(':n bills', ['n' => $N($metrics['owing_bills'])])" />
            <x-ui.kpi-tile :label="__('Owed by corporates')" :value="$metrics['corporate_owed']" unit="taka" icon="bi-building" variant="warning" :href="$L('admin.billing.corporates.receivables', 'billing.corporate.view')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Discounts given')" :value="$c['discounts']" :previous="$p['discounts']" unit="taka" icon="bi-tag" variant="secondary" :href="$L('admin.billing.reports.discounts', 'billing.report.view')" good-direction="none" />
            <x-ui.kpi-tile :label="__('Refunds paid')" :value="$c['refunds']" :previous="$p['refunds']" unit="taka" icon="bi-arrow-counterclockwise" variant="danger" :href="$L('admin.billing.refunds.index', 'billing.refund.request')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Cashier sessions open')" :value="$metrics['open_sessions']" unit="count" icon="bi-person-badge" variant="info" :href="$L('admin.billing.cashier.index', 'billing.cashier.view')" good-direction="none" />
        </div>
        <div class="row g-3 mb-3">
            <div class="col-lg-8"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Billed and collected each day') }} <span class="small text-muted">· {{ $period->label() }}</span></h3></div><div class="card-body">
                <x-ui.chart kind="bar" :rows="$metrics['per_day']" x="day" :series="[['key' => 'billed', 'label' => 'Billed'], ['key' => 'collected', 'label' => 'Collected']]" unit="taka" />
            </div></div></div>
            <div class="col-lg-4"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('How the money came') }}</h3></div><div class="card-body">
                <x-ui.chart kind="hbar" :rows="$metrics['by_method']" x="method" :series="[['key' => 'taka', 'label' => 'Taka']]" unit="taka" />
            </div></div></div>
        </div>
        <div class="card"><div class="card-header"><h3 class="card-title">{{ __('What is owed, by how old the bill is') }}</h3></div><div class="card-body">
            <x-ui.chart kind="hbar" :rows="$metrics['aging']" x="age" :series="[['key' => 'owed', 'label' => 'Owed']]" unit="taka" />
        </div></div>
    </x-ui.dashboard>
@stop
