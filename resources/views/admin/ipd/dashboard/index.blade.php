@extends('layouts.adminlte')

@section('page_title', __('IPD (Indoor)'))

@section('page_content')
    @php
        $L = fn ($route, $perm, $q = []) => \App\Support\Dashboard\Link::to($route, $perm, $q);
        $beds = $L('admin.ipd.bed-board.index', 'ipd.bed.view');
        $adm = $L('admin.ipd.admissions.index', 'ipd.admission.view');
        $dis = $L('admin.ipd.discharge.index', 'ipd.discharge.view');
    @endphp
    <x-ui.dashboard :question="__('Where are the beds, and who is coming in and going out today?')" :audience="__('For the ward and bed manager and the director')" :as-of="$asOf" :refresh="config('ux.dashboard.refresh_seconds.management')">
        <div class="row row-cols-2 row-cols-md-4 row-cols-xl-5 g-2 mb-3">
            <x-ui.kpi-tile :label="__('Bed occupancy')" :value="$summary['occupancy_rate']" unit="percent" icon="bi-grid-3x3-gap" variant="info" :href="$beds" good-direction="none" :target="app(\App\Services\Dashboard\Targets::class)->get('ipd_occupancy')" :note="__(':o of :b usable beds in use', ['o' => \App\Support\Dashboard\Numbers::number($summary['occupied_beds']), 'b' => \App\Support\Dashboard\Numbers::number($summary['operational_beds'])])" />
            <x-ui.kpi-tile :label="__('Beds available')" :value="$summary['available_beds']" unit="count" icon="bi-door-open" variant="success" :href="$L('admin.ipd.bed-availability.index', 'ipd.bed.view')" good-direction="up" />
            <x-ui.kpi-tile :label="__('Inpatients now')" :value="$summary['current_inpatients']" unit="count" icon="bi-hospital" variant="primary" :href="$adm" good-direction="none" />
            <x-ui.kpi-tile :label="__('Admitted today')" :value="$summary['admissions_today']" :previous="$summary['admissions_usual']" unit="count" icon="bi-box-arrow-in-right" variant="primary" :href="$adm" good-direction="none" :compare-label="__('vs the usual weekday')" />
            <x-ui.kpi-tile :label="__('Discharged today')" :value="$summary['discharges_today']" :previous="$summary['discharges_usual']" unit="count" icon="bi-box-arrow-right" variant="success" :href="$dis" good-direction="none" :compare-label="__('vs the usual weekday')" />
            <x-ui.kpi-tile :label="__('Expected to leave today')" :value="$summary['expected_discharges_today']" unit="count" icon="bi-calendar-check" variant="info" :href="$dis" good-direction="none" />
            <x-ui.kpi-tile :label="__('Delayed discharges')" :value="$summary['delayed_discharges']" unit="count" icon="bi-exclamation-triangle" variant="danger" :href="$dis" good-direction="down" />
            <x-ui.kpi-tile :label="__('Admission requests waiting')" :value="$summary['pending_admission_requests']" unit="count" icon="bi-inbox" variant="warning" :href="$L('admin.ipd.admission-requests.index', 'ipd.admission.view')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Transfers waiting')" :value="$summary['pending_transfers']" unit="count" icon="bi-arrow-left-right" variant="warning" :href="$L('admin.ipd.transfers.index', 'ipd.transfer.view')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Transfers today')" :value="$summary['transfers_today']" unit="count" icon="bi-shuffle" variant="secondary" good-direction="none" />
        </div>
        <div class="row g-3 mb-3">
            <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Occupancy by ward') }}</h3></div><div class="card-body">
                @if(count($summary['by_ward']) > 0)<x-ui.chart kind="hbar" :rows="$summary['by_ward']" x="ward" :series="[['key' => 'rate', 'label' => 'Occupancy']]" unit="percent" />@else<x-ui.empty-state :title="__('No wards or beds are set up.')" :hint="__('Add wards, rooms and beds under IPD settings.')" icon="bi-grid-3x3-gap" />@endif
            </div></div></div>
            <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Admitted and discharged, last 14 days') }}</h3></div><div class="card-body">
                <x-ui.chart kind="bar" :rows="$summary['flow']" x="day" :series="[['key' => 'admitted', 'label' => 'Admitted'], ['key' => 'discharged', 'label' => 'Discharged']]" />
            </div></div></div>
        </div>
        <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Beds not in use') }}</h3></div><div class="card-body small">
            {{ __('Reserved') }}: <strong>{{ \App\Support\Dashboard\Numbers::number($summary['reserved_beds']) }}</strong> · {{ __('Being cleaned') }}: <strong>{{ \App\Support\Dashboard\Numbers::number($summary['cleaning_beds']) }}</strong> · {{ __('Isolation') }}: <strong>{{ \App\Support\Dashboard\Numbers::number($summary['isolation_beds']) }}</strong> · {{ __('Under maintenance') }}: <strong>{{ \App\Support\Dashboard\Numbers::number($summary['maintenance_beds']) }}</strong>
        </div></div>
    </x-ui.dashboard>
@stop
