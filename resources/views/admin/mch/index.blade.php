@extends('layouts.adminlte')

@section('page_title', __('Mothers and babies'))

@section('page_content')
    @include('admin.mch._flash')
    @php $L = fn ($route, $perm, $q = []) => \App\Support\Dashboard\Link::to($route, $perm, $q); $N = fn ($v) => \App\Support\Dashboard\Numbers::number($v); $c = $s['current']; $p = $s['previous']; @endphp
    <x-ui.dashboard :question="__('Are expectant mothers being seen in time, and are babies getting their vaccines on schedule?')" :audience="__('For the obstetrics and paediatrics heads and the midwives')" :period="$period" :periods="['7d', '30d', '90d', 'month', 'last_month', 'custom']" :as-of="$asOf">
        <div class="row row-cols-2 row-cols-md-4 row-cols-xl-5 g-2 mb-3">
            <x-ui.kpi-tile :label="__('Women being followed')" :value="$s['pregnant']" unit="count" icon="bi-person-heart" variant="primary" :href="$L('admin.mch.pregnancies', 'mch.pregnancy.view')" good-direction="none" />
            <x-ui.kpi-tile :label="__('High-risk pregnancies')" :value="$s['high_risk']" unit="count" icon="bi-exclamation-triangle" variant="danger" :href="$L('admin.mch.pregnancies', 'mch.pregnancy.view')" good-direction="none" />
            <x-ui.kpi-tile :label="__('Due within 30 days')" :value="$s['due_30']" unit="count" icon="bi-calendar-event" variant="info" :href="$L('admin.mch.pregnancies', 'mch.pregnancy.view', ['due' => 1])" good-direction="none" />
            <x-ui.kpi-tile :label="__('Past their due date')" :value="$s['past_date']" unit="count" icon="bi-hourglass-bottom" variant="warning" good-direction="down" />
            <x-ui.kpi-tile :label="__('Not seen for 6 weeks')" :value="$s['no_visit_6w']" unit="count" icon="bi-person-x" variant="warning" good-direction="down" />
            <x-ui.kpi-tile :label="__('Children with a vaccine due')" :value="$s['children_due']" unit="count" icon="bi-shield-plus" variant="info" :href="$L('admin.mch.immunisation', 'mch.immunisation.view')" good-direction="down" :note="__(':n overdue', ['n' => $N($s['children_overdue'])])" />
            <x-ui.kpi-tile :label="__('Deliveries')" :value="$c['deliveries']" :previous="$p['deliveries']" unit="count" icon="bi-balloon-heart" variant="success" good-direction="none" :note="__(':n stillbirths', ['n' => $N($c['stillbirths'])])" />
            <x-ui.kpi-tile :label="__('Caesarean share')" :value="$c['caesarean_percent']" :previous="$p['caesarean_percent']" unit="percent" icon="bi-scissors" variant="secondary" good-direction="none" :target="app(\App\Services\Dashboard\Targets::class)->get('caesarean_percent')" />
            <x-ui.kpi-tile :label="__('Babies under 2,500 g')" :value="$c['low_weight_percent']" :previous="$p['low_weight_percent']" unit="percent" icon="bi-droplet-half" variant="warning" good-direction="down" />
            <x-ui.kpi-tile :label="__('Antenatal visits')" :value="$c['anc_visits']" :previous="$p['anc_visits']" unit="count" icon="bi-clipboard2-pulse" variant="primary" good-direction="none" :note="__(':n vaccine doses given', ['n' => $N($c['doses'])])" />
        </div>
        <div class="row g-3">
            <div class="col-lg-8"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Deliveries each day') }} <span class="small text-muted">· {{ $period->label() }}</span></h3></div><div class="card-body">
                <x-ui.chart kind="bar" :rows="$s['per_day']" x="day" :series="[['key' => 'deliveries', 'label' => 'Deliveries']]" /></div></div></div>
            <div class="col-lg-4"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('How babies were delivered') }}</h3></div><div class="card-body">
                @if($s['by_mode'])<x-ui.chart kind="hbar" :rows="$s['by_mode']" x="mode" :series="[['key' => 'deliveries', 'label' => 'Deliveries']]" />@else<x-ui.empty-state :title="__('No delivery in this period.')" icon="bi-balloon-heart" />@endif</div></div></div>
        </div>
    </x-ui.dashboard>
@stop
