@extends('layouts.adminlte')

@section('page_title', __('Disease watch'))

@section('page_content')
    @include('admin.watch._flash')
    @php $L = fn ($route, $perm, $q = []) => \App\Support\Dashboard\Link::to($route, $perm, $q); $N = fn ($v) => \App\Support\Dashboard\Numbers::number($v); $c = $s['current']; $p = $s['previous']; @endphp
    <x-ui.dashboard :question="__('Are cases of any disease we must watch rising, and has what we found been reported?')" :audience="__('For the infection-control and public-health officers and the director')" :period="$period" :periods="['7d', '30d', '90d', 'month', 'last_month', 'custom']" :as-of="$asOf">
        @if($canScan)<form method="POST" action="{{ route('admin.watch.scan') }}" class="mb-3 d-print-none">@csrf<button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i> {{ __('Look now') }}</button></form>@endif
        <div class="row row-cols-2 row-cols-md-4 row-cols-xl-5 g-2 mb-3">
            <x-ui.kpi-tile :label="__('New signals')" :value="$s['new']" unit="count" icon="bi-bell" variant="danger" :href="$L('admin.watch.signals', 'watch.signal.view', ['status' => 'new'])" good-direction="down" />
            <x-ui.kpi-tile :label="__('Being looked into')" :value="$s['investigating']" unit="count" icon="bi-search" variant="warning" :href="$L('admin.watch.signals', 'watch.signal.view', ['status' => 'investigating'])" good-direction="down" />
            <x-ui.kpi-tile :label="__('Confirmed, not yet reported')" :value="$s['to_report']" unit="count" icon="bi-megaphone" variant="danger" :href="$L('admin.watch.signals', 'watch.signal.view', ['unreported' => 1])" good-direction="down" />
            <x-ui.kpi-tile :label="__('Conditions watched')" :value="$s['watched']" unit="count" icon="bi-list-check" variant="secondary" :href="$L('admin.watch.conditions', 'watch.signal.view')" good-direction="none" />
            <x-ui.kpi-tile :label="__('Patients with a watched condition')" :value="$c['patients']" :previous="$p['patients']" unit="count" icon="bi-virus2" variant="primary" good-direction="none" />
            <x-ui.kpi-tile :label="__('Signals raised')" :value="$c['signals']" unit="count" icon="bi-flag" variant="warning" good-direction="none" :note="__(':n confirmed', ['n' => $N($c['confirmed'])])" />
        </div>
        <div class="row g-3">
            <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Patients by condition') }} <span class="small text-muted">· {{ $period->label() }}</span></h3></div><div class="card-body">
                @if($s['by_condition'])<x-ui.chart kind="hbar" :rows="$s['by_condition']" x="condition" :series="[['key' => 'cases', 'label' => 'Patients']]" />@else<x-ui.empty-state :title="__('No patient with a watched condition in this period.')" icon="bi-virus2" />@endif</div></div></div>
            <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Where they live (district)') }}</h3></div><div class="card-body">
                @if($s['by_district'])<x-ui.chart kind="hbar" :rows="$s['by_district']" x="district" :series="[['key' => 'cases', 'label' => 'Patients']]" />@else<x-ui.empty-state :title="__('No home district is recorded for these patients.')" icon="bi-pin-map" />@endif
                @if($s['no_district'] > 0)<div class="small text-muted mt-2">{{ __(':n patient(s) have no home district recorded.', ['n' => $N($s['no_district'])]) }}</div>@endif</div></div></div>
        </div>
    </x-ui.dashboard>
@stop
