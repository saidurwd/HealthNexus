@extends('layouts.adminlte')

@section('page_title', __('Emergency performance'))

@section('page_content')
    @php
        $K = $kpi; $P = $previous;
        $mid = fn ($x) => $x['median'] ?? null;
        $p90 = fn ($x) => $x ? __('90% within :m', ['m' => \App\Support\Dashboard\Numbers::value($x['p90'], 'minutes')]) : null;
    @endphp
    <x-ui.dashboard :question="__('Is the emergency department meeting its targets?')" :audience="__('For the emergency lead and the director')" :period="$period" :periods="$periods" :as-of="$asOf">
        <div class="row row-cols-2 row-cols-md-4 row-cols-xl-4 g-2 mb-3">
            <x-ui.kpi-tile :label="__('Arrivals')" :value="$K['arrivals']" :previous="$P['arrivals']" unit="count" icon="bi-hospital-fill" variant="danger" good-direction="none" />
            <x-ui.kpi-tile :label="__('In the department now')" :value="$K['active_now']" unit="count" icon="bi-people" variant="info" :href="\App\Support\Dashboard\Link::to('admin.ed.board', 'ed.board.view')" good-direction="none" />
            <x-ui.kpi-tile :label="__('Door to triage (median)')" :value="$mid($K['door_to_triage'])" :previous="$mid($P['door_to_triage'])" unit="minutes" icon="bi-stopwatch" variant="warning" good-direction="down" :target="$targets['triage']" :note="$p90($K['door_to_triage'])" />
            <x-ui.kpi-tile :label="__('Door to doctor (median)')" :value="$mid($K['door_to_doctor'])" :previous="$mid($P['door_to_doctor'])" unit="minutes" icon="bi-person-badge" variant="warning" good-direction="down" :target="$targets['doctor']" :note="$p90($K['door_to_doctor'])" />
            <x-ui.kpi-tile :label="__('Seen within target')" :value="$K['door_to_doctor_met_percent']" :previous="$P['door_to_doctor_met_percent']" unit="percent" icon="bi-check2-circle" variant="success" good-direction="up" :target="$targets['seen']" />
            <x-ui.kpi-tile :label="__('Length of stay (median)')" :value="$mid($K['length_of_stay'])" :previous="$mid($P['length_of_stay'])" unit="minutes" icon="bi-clock-history" variant="secondary" good-direction="down" :note="$p90($K['length_of_stay'])" />
            <x-ui.kpi-tile :label="__('Left without being seen')" :value="$K['lwbs_percent']" :previous="$P['lwbs_percent']" unit="percent" icon="bi-box-arrow-right" variant="danger" good-direction="down" :target="$targets['lwbs']" />
        </div>
        <div class="row g-3 mb-3">
            <div class="col-lg-7"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Arrivals by day') }}</h3></div><div class="card-body"><x-ui.chart kind="bar" :rows="$perDay" x="day" :series="[['key' => 'arrivals', 'label' => 'Arrivals']]" /></div></div></div>
            <div class="col-lg-5"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Arrivals by hour of the day') }}</h3></div><div class="card-body"><x-ui.chart kind="bar" :rows="$byHour" x="hour" :series="[['key' => 'arrivals', 'label' => 'Arrivals']]" :height="190" /></div></div></div>
        </div>
        <div class="row g-3">
            <div class="col-md-6"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Arrivals by triage level') }}</h3></div><div class="card-body">
                @if(count($K['by_level']) > 0)<x-ui.chart kind="hbar" :rows="collect($K['by_level'])->map(fn ($n, $l) => ['level' => $l === 'untriaged' ? __('Not triaged') : __('Level').' '.$l, 'n' => $n])->values()->all()" x="level" :series="[['key' => 'n', 'label' => 'Arrivals']]" />@else<x-ui.empty-state :title="__('No arrivals in this period.')" :hint="__('Choose a longer period.')" icon="bi-hospital-fill" />@endif
            </div></div></div>
            <div class="col-md-6"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Outcomes') }}</h3></div><div class="card-body">
                @if(count($K['by_disposition']) > 0)<x-ui.chart kind="hbar" :rows="collect($K['by_disposition'])->map(fn ($n, $t) => ['type' => __(\Illuminate\Support\Str::headline($t)), 'n' => $n])->values()->all()" x="type" :series="[['key' => 'n', 'label' => 'Patients']]" />@else<x-ui.empty-state :title="__('No one has left yet in this period.')" icon="bi-box-arrow-right" />@endif
            </div></div></div>
        </div>
    </x-ui.dashboard>
@stop
