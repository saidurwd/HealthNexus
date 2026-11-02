@extends('layouts.adminlte')

@section('page_title', __('Facility and support services'))

@section('page_content')
    @include('admin.facility._flash')
    <p class="text-muted small">{{ __('Anyone can report a broken device: open the equipment and press "Report a fault", or scan the label on it.') }}</p>
    <div class="row g-2 mb-3">
        @foreach([['facility.asset.view', 'admin.facility.assets', 'Equipment', 'bi-cpu'], ['facility.workorder.view', 'admin.facility.workorders', 'Work orders', 'bi-wrench-adjustable'], ['facility.housekeeping.view', 'admin.facility.housekeeping', 'Cleaning', 'bi-droplet'], ['facility.cssd.view', 'admin.facility.cssd', 'Sterile services', 'bi-shield-check'],
            ['facility.linen.view', 'admin.facility.linen', 'Linen', 'bi-basket'], ['facility.waste.view', 'admin.facility.waste', 'Biomedical waste', 'bi-trash3'], ['facility.diet.view', 'admin.facility.diets', 'Diets and kitchen', 'bi-egg-fried'], ['facility.ambulance.view', 'admin.facility.ambulances', 'Ambulances', 'bi-truck-front'],
            ['facility.mortuary.view', 'admin.facility.mortuary', 'Mortuary', 'bi-door-closed'], ['facility.visitor.view', 'admin.facility.visitors', 'Visitors and attendants', 'bi-people'], ['facility.visitor.gate', 'admin.facility.gate', 'The gate', 'bi-box-arrow-in-right']] as [$perm, $route, $label, $icon])
            @can($perm)<div class="col-6 col-md-3"><a class="btn btn-outline-secondary w-100 text-start" href="{{ route($route) }}"><i class="bi {{ $icon }}"></i> {{ __($label) }}</a></div>@endcan
        @endforeach
    </div>
    @if($s)
    <div class="row row-cols-2 row-cols-md-4 g-3 mb-3">
        <x-ui.kpi-tile :label="__('Equipment in the register')" :value="$s['assets']" icon="bi-cpu" variant="primary" :href="route('admin.facility.assets')" />
        <x-ui.kpi-tile :label="__('Equipment not in use')" :value="$s['assets_down']" icon="bi-exclamation-octagon" variant="danger" :href="route('admin.facility.assets', ['status' => 'out_of_service'])" />
        <x-ui.kpi-tile :label="__('High-risk equipment that must not be used')" :value="$s['high_risk_blocked']" icon="bi-slash-circle" variant="danger" />
        <x-ui.kpi-tile :label="__('Calibrations overdue')" :value="$s['calibrations_overdue']" icon="bi-alarm" variant="warning" />
        <x-ui.kpi-tile :label="__('Work orders open')" :value="$s['work_orders_open']" icon="bi-wrench-adjustable" variant="primary" :href="route('admin.facility.workorders')" />
        <x-ui.kpi-tile :label="__('Urgent work orders')" :value="$s['work_orders_urgent']" icon="bi-lightning" variant="danger" />
        <x-ui.kpi-tile :label="__('Repairs waiting to be checked')" :value="$s['work_orders_to_verify']" icon="bi-patch-check" variant="info" :href="route('admin.facility.workorders', ['status' => 'to_check'])" />
        <x-ui.kpi-tile :label="__('Hours of downtime (30 days)')" :value="$s['downtime_hours']" icon="bi-hourglass-split" variant="secondary" />
        <x-ui.kpi-tile :label="__('Beds waiting to be cleaned')" :value="$s['cleaning_waiting']" icon="bi-droplet" variant="warning" :href="route('admin.facility.housekeeping')" />
        <x-ui.kpi-tile :label="__('Average bed turnaround (minutes)')" :value="$s['turnaround_minutes'] ?? '—'" icon="bi-stopwatch" variant="{{ $s['turnaround_minutes'] !== null && $s['turnaround_minutes'] > $s['turnaround_target'] ? 'danger' : 'success' }}" />
        <x-ui.kpi-tile :label="__('Sterile batches to release')" :value="$s['batches_to_release']" icon="bi-shield-check" variant="info" :href="route('admin.facility.cssd')" />
        <x-ui.kpi-tile :label="__('Linen below its level')" :value="$s['linen_below_par']" icon="bi-basket" variant="warning" :href="route('admin.facility.linen')" />
        <x-ui.kpi-tile :label="__('Waste in 30 days (kg)')" :value="$s['waste_kg']" icon="bi-trash3" variant="secondary" :href="route('admin.facility.waste')" />
        <x-ui.kpi-tile :label="__('Diets in force')" :value="$s['diets_active']" icon="bi-egg-fried" variant="primary" :href="route('admin.facility.diets')" />
        <x-ui.kpi-tile :label="__('Ambulances free / on a trip')" :value="$s['ambulances_available'].' / '.$s['ambulances_on_trip']" icon="bi-truck-front" variant="primary" :href="route('admin.facility.ambulances')" />
        <x-ui.kpi-tile :label="__('Ambulance requests waiting')" :value="$s['trips_waiting']" icon="bi-telephone-inbound" variant="{{ $s['trips_waiting'] ? 'danger' : 'success' }}" />
        <x-ui.kpi-tile :label="__('Mortuary: places free / in use')" :value="$s['mortuary']['free'].' / '.$s['mortuary']['used']" icon="bi-door-closed" variant="secondary" :href="route('admin.facility.mortuary')" />
        <x-ui.kpi-tile :label="__('Bodies waiting to be received')" :value="$s['mortuary_waiting']" icon="bi-inbox" variant="warning" />
        <x-ui.kpi-tile :label="__('Visitor passes in force')" :value="$s['passes_active']" icon="bi-ticket-perforated" variant="primary" :href="route('admin.facility.visitors')" />
        <x-ui.kpi-tile :label="__('People inside now')" :value="$s['visitors_inside']" icon="bi-people" variant="info" />
        <x-ui.kpi-tile :label="__('Refused at the gate (30 days)')" :value="$s['gate_refusals']" icon="bi-slash-circle" variant="secondary" />
    </div>
    @endif
@stop
