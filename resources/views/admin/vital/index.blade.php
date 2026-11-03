@extends('layouts.adminlte')

@section('page_title', __('Births, deaths and police cases'))

@section('page_content')
    @include('admin.vital._flash')
    @php $L = fn ($route, $perm, $q = []) => \App\Support\Dashboard\Link::to($route, $perm, $q); $N = fn ($v) => \App\Support\Dashboard\Numbers::number($v); $c = $s['current']; $p = $s['previous']; @endphp
    <x-ui.dashboard :question="__('Is every birth notified and every death certified, and are the police cases being handled in time?')" :audience="__('For the records officer, the medical superintendent and the director')" :period="$period" :periods="['7d', '30d', '90d', 'month', 'last_month', 'custom']" :as-of="$asOf">
        <div class="row row-cols-2 row-cols-md-4 row-cols-xl-5 g-2 mb-3">
            <x-ui.kpi-tile :label="__('Deaths without a certificate')" :value="$s['awaiting_certificate']" unit="count" icon="bi-file-earmark-medical" variant="danger" :href="$L('admin.vital.deaths', 'vital.death.view')" good-direction="down" :note="$s['oldest_awaiting_hours'] !== null ? __('oldest :n hours', ['n' => $N($s['oldest_awaiting_hours'])]) : null" />
            <x-ui.kpi-tile :label="__('Certified, not given to the family')" :value="$s['certified_not_issued']" unit="count" icon="bi-hourglass-split" variant="warning" :href="$L('admin.vital.deaths', 'vital.death.view', ['status' => 'certified'])" good-direction="down" :note="__(':n held by a police case', ['n' => $N($s['held_by_police'])])" />
            <x-ui.kpi-tile :label="__('Police cases open')" :value="$s['police_cases_open']" unit="count" icon="bi-shield-exclamation" variant="warning" :href="$L('admin.vital.medicolegal', 'vital.medicolegal.view')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Police not told in time')" :value="$s['police_not_told_in_time']" unit="count" icon="bi-telephone-x" variant="danger" :href="$L('admin.vital.medicolegal', 'vital.medicolegal.view', ['status' => 'open'])" good-direction="down" />
            <x-ui.kpi-tile :label="__('Births to follow up')" :value="$s['births_to_follow_up']" unit="count" icon="bi-clipboard2-pulse" variant="warning" :href="$L('admin.vital.births', 'vital.birth.view', ['followup' => 1])" good-direction="down" :note="__('issued, not registered after :n days', ['n' => $N(config('vital.registration_followup_days'))])" />
            <x-ui.kpi-tile :label="__('Births')" :value="$c['births']" :previous="$p['births']" unit="count" icon="bi-balloon-heart" variant="success" :href="$L('admin.vital.births', 'vital.birth.view')" good-direction="none" :note="__(':n stillbirths', ['n' => $N($c['stillbirths'])])" />
            <x-ui.kpi-tile :label="__('Deaths')" :value="$c['deaths']" :previous="$p['deaths']" unit="count" icon="bi-heart-pulse" variant="secondary" :href="$L('admin.vital.deaths', 'vital.death.view')" good-direction="none" />
            <x-ui.kpi-tile :label="__('Typical hours from death to certificate')" :value="$c['hours_to_certificate']['median'] ?? null" :previous="$p['hours_to_certificate']['median'] ?? null" unit="hours" icon="bi-stopwatch" variant="info" good-direction="down" :note="$c['hours_to_certificate'] ? __('9 in 10 within :m hours · :n certificates', ['m' => $N($c['hours_to_certificate']['p90']), 'n' => $N($c['hours_to_certificate']['n'])]) : __('No certificate in this period.')" />
        </div>
        <div class="row g-3">
            <div class="col-lg-8"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Births and deaths each day') }} <span class="small text-muted">· {{ $period->label() }}</span></h3></div><div class="card-body">
                <x-ui.chart kind="bar" :rows="$s['per_day']" x="day" :series="[['key' => 'births', 'label' => 'Births'], ['key' => 'deaths', 'label' => 'Deaths']]" />
            </div></div></div>
            <div class="col-lg-4"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Deaths by manner') }}</h3></div><div class="card-body">
                @if($s['by_manner'])<x-ui.chart kind="hbar" :rows="$s['by_manner']" x="manner" :series="[['key' => 'deaths', 'label' => 'Deaths']]" />
                @else<x-ui.empty-state :title="__('No death was certified in this period.')" icon="bi-heart-pulse" />@endif
            </div></div></div>
        </div>
    </x-ui.dashboard>
@stop
