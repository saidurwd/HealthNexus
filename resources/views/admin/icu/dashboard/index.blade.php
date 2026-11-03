@extends('layouts.adminlte')

@section('page_title', __('ICU / Critical Care'))

@section('page_content')
    @php $L = fn ($route, $perm, $q = []) => \App\Support\Dashboard\Link::to($route, $perm, $q); @endphp
    <x-ui.dashboard :question="__('Who is critical, and are the alerts being answered?')" :audience="__('For the ICU lead and the nurse in charge')" :as-of="$asOf" :refresh="config('ux.dashboard.refresh_seconds.board')">
        <div class="row row-cols-2 row-cols-md-4 row-cols-xl-6 g-2 mb-3">
            <x-ui.kpi-tile :label="__('Patients in ICU')" :value="$metrics['current_patients']" unit="count" icon="bi-heart-pulse" variant="danger" :href="$L('admin.icu.episodes.index', 'icu.episode.view')" good-direction="none" />
            <x-ui.kpi-tile :label="__('Bed occupancy')" :value="$metrics['occupancy']" unit="percent" icon="bi-grid-3x3-gap" variant="info" :href="$L('admin.icu.bed-board', 'icu.bed.view')" good-direction="none" :note="__(':a ICU beds free', ['a' => \App\Support\Dashboard\Numbers::number($metrics['beds_available'])])" />
            <x-ui.kpi-tile :label="__('Alerts not acknowledged')" :value="$metrics['unacknowledged_alerts']" unit="count" icon="bi-bell" variant="danger" :href="$L('admin.icu.alerts.index', 'icu.alert.view')" good-direction="down" :note="$metrics['oldest_alert_minutes'] !== null ? __('oldest waiting :m', ['m' => \App\Support\Dashboard\Numbers::value($metrics['oldest_alert_minutes'], 'minutes')]) : null" />
            <x-ui.kpi-tile :label="__('Admission requests waiting')" :value="$metrics['pending_requests']" unit="count" icon="bi-inbox" variant="warning" :href="$L('admin.icu.admission-requests.index', 'icu.admission_request.view')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Admitted today')" :value="$metrics['admissions_today']" unit="count" icon="bi-box-arrow-in-right" variant="primary" good-direction="none" />
            <x-ui.kpi-tile :label="__('Discharged today')" :value="$metrics['discharges_today']" unit="count" icon="bi-box-arrow-right" variant="success" good-direction="none" />
        </div>
        <div class="row g-3">
            <div class="col-lg-5"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Alerts raised, last 14 days') }}</h3></div><div class="card-body">
                <x-ui.chart kind="bar" :rows="$metrics['alerts']" x="day" :series="[['key' => 'raised', 'label' => 'Alerts']]" />
            </div></div></div>
            <div class="col-lg-7"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Alerts waiting for someone') }}</h3></div>
                <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Patient') }}</th><th>{{ __('Alert') }}</th><th>{{ __('Since') }}</th></tr></thead><tbody>
                    @forelse($criticalAlerts as $a)<tr><td>{{ $a->episode?->patient?->display_name }}</td><td>{{ $a->rule }}</td><td>{{ $a->triggered_at?->timezone('Asia/Dhaka')->format('d M, H:i') }}</td></tr>@empty<tr><td colspan="3"><x-ui.empty-state :title="__('No alert is waiting.')" icon="bi-check2-circle" /></td></tr>@endforelse</tbody></table></div></div></div>
        </div>
    </x-ui.dashboard>
@stop
