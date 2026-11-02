@extends('layouts.adminlte')

@section('page_title', __('Radiology'))

@section('page_content')
    @php $L = fn ($route, $perm, $q = []) => \App\Support\Dashboard\Link::to($route, $perm, $q); $N = fn ($v) => \App\Support\Dashboard\Numbers::number($v); $c = $metrics['current']; $p = $metrics['previous']; @endphp
    <x-ui.dashboard :question="__('Who is waiting for a scan, and which finished scans still have no report?')" :audience="__('For the radiology head and the radiologists')" :period="$period" :periods="['today', '7d', '30d', '90d', 'month', 'last_month', 'custom']" :as-of="$asOf">
        <div class="row row-cols-2 row-cols-md-4 row-cols-xl-5 g-2 mb-3">
            <x-ui.kpi-tile :label="__('Waiting for a scan')" :value="$metrics['waiting_patients']" unit="count" icon="bi-hourglass-split" variant="info" :href="$L('admin.radiology.worklist.index', 'radiology.worklist.view')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Being scanned')" :value="$metrics['in_progress']" unit="count" icon="bi-activity" variant="primary" good-direction="none" />
            <x-ui.kpi-tile :label="__('Scanned, not reported')" :value="$metrics['awaiting_reporting']" unit="count" icon="bi-file-earmark-medical" variant="warning" :href="$L('admin.radiology.orders.index', 'radiology.order.view')" good-direction="down" :note="$metrics['oldest_waiting_minutes'] !== null ? __('oldest :n minutes', ['n' => $N($metrics['oldest_waiting_minutes'])]) : null" />
            <x-ui.kpi-tile :label="__('Urgent, not reported')" :value="$metrics['urgent_waiting']" unit="count" icon="bi-lightning-charge" variant="danger" :href="$L('admin.radiology.orders.index', 'radiology.order.view')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Critical findings unseen')" :value="$metrics['critical_unacknowledged']" unit="count" icon="bi-exclamation-triangle" variant="danger" :href="$L('admin.radiology.critical-findings.index', 'radiology.critical_finding.view')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Reports to approve')" :value="$metrics['reports_pending_approval']" unit="count" icon="bi-patch-check" variant="warning" :href="$L('admin.radiology.reports.index', 'radiology.report.view')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Studies done')" :value="$c['completed']" :previous="$p['completed']" unit="count" icon="bi-camera" variant="success" good-direction="none" />
            <x-ui.kpi-tile :label="__('Typical time to report')" :value="$c['tat']['median'] ?? null" :previous="$p['tat']['median'] ?? null" unit="minutes" icon="bi-stopwatch" variant="info" good-direction="down" :target="app(\App\Services\Dashboard\Targets::class)->get('radiology_tat')" :note="$c['tat'] ? __('9 in 10 within :m min · :n reports', ['m' => $N($c['tat']['p90']), 'n' => $N($c['tat']['n'])]) : __('No reports in this period.')" />
        </div>
        <div class="row g-3">
            <div class="col-lg-5"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Studies by type of scan') }} <span class="small text-muted">· {{ $period->label() }}</span></h3></div><div class="card-body">
                <x-ui.chart kind="hbar" :rows="$metrics['by_modality']" x="modality" :series="[['key' => 'studies', 'label' => 'Studies']]" />
            </div></div></div>
            <div class="col-lg-7"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Studies per day') }}</h3></div><div class="card-body">
                <x-ui.chart kind="bar" :rows="$metrics['per_day']" x="day" :series="[['key' => 'studies', 'label' => 'Studies']]" />
            </div></div></div>
        </div>
    </x-ui.dashboard>
@stop
