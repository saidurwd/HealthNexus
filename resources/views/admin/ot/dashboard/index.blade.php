@extends('layouts.adminlte')

@section('page_title', __('OT / Surgery'))

@section('page_content')
    @php $L = fn ($route, $perm, $q = []) => \App\Support\Dashboard\Link::to($route, $perm, $q); $list = $L('admin.ot.surgeries.index', 'ot.surgery.view'); @endphp
    <x-ui.dashboard :question="__('Is the theatre list running on time?')" :audience="__('For the theatre manager and the surgeons')" :as-of="$asOf" :refresh="config('ux.dashboard.refresh_seconds.board')">
        <div class="row row-cols-2 row-cols-md-4 row-cols-xl-5 g-2 mb-3">
            <x-ui.kpi-tile :label="__('Cases today')" :value="$metrics['scheduled_today']" unit="count" icon="bi-scissors" variant="primary" :href="$list" good-direction="none" :note="__(':n done', ['n' => \App\Support\Dashboard\Numbers::number($metrics['completed_today'])])" />
            <x-ui.kpi-tile :label="__('In theatre now')" :value="$metrics['in_procedure']" unit="count" icon="bi-activity" variant="danger" :href="$list" good-direction="none" />
            <x-ui.kpi-tile :label="__('In recovery')" :value="$metrics['in_recovery']" unit="count" icon="bi-heart-pulse" variant="info" :href="$list" good-direction="none" />
            <x-ui.kpi-tile :label="__('Called off today')" :value="$metrics['cancelled_today']" unit="count" icon="bi-x-octagon" variant="warning" :href="$list" good-direction="down" />
            <x-ui.kpi-tile :label="__('Requests waiting')" :value="$metrics['pending_requests']" unit="count" icon="bi-inbox" variant="warning" :href="$L('admin.ot.surgery-requests.index', 'ot.surgery.view')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Average late start')" :value="$metrics['average_start_delay']" unit="minutes" icon="bi-stopwatch" variant="warning" good-direction="down" :target="app(\App\Services\Dashboard\Targets::class)->get('ot_start_delay')" :note="$metrics['delay_cases'] > 0 ? __('over :n cases in 30 days', ['n' => \App\Support\Dashboard\Numbers::number($metrics['delay_cases'])]) : __('No started cases in 30 days.')" />
            <x-ui.kpi-tile :label="__('Cases called off (30 days)')" :value="$metrics['called_off_rate']" unit="percent" icon="bi-slash-circle" variant="secondary" good-direction="down" :note="$metrics['planned_30d'] > 0 ? __(':c of :p planned cases', ['c' => \App\Support\Dashboard\Numbers::number($metrics['called_off_30d']), 'p' => \App\Support\Dashboard\Numbers::number($metrics['planned_30d'])]) : null" />
            <x-ui.kpi-tile :label="__('Theatres free')" :value="$metrics['theatres_available']" unit="count" icon="bi-door-open" variant="success" :href="$L('admin.ot.theatres.index', 'ot.theatre.view')" good-direction="up" :note="__(':n in use', ['n' => \App\Support\Dashboard\Numbers::number($metrics['theatres_occupied'])])" />
        </div>
        <div class="row g-3">
            <div class="col-lg-4"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Cases per day, last 14 days') }}</h3></div><div class="card-body">
                <x-ui.chart kind="bar" :rows="$metrics['per_day']" x="day" :series="[['key' => 'cases', 'label' => 'Cases']]" />
            </div></div></div>
            <div class="col-lg-8"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Today\'s list') }}</h3></div>
                <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Time') }}</th><th>{{ __('Theatre') }}</th><th>{{ __('Procedure') }}</th><th>{{ __('Surgeon') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
                    @forelse($todaysList as $s)<tr><td>{{ $s->scheduled_start_at?->timezone('Asia/Dhaka')->format('H:i') }}</td><td>{{ $s->theatre?->name }}</td><td>{{ $s->primaryProcedure?->name }}</td><td>{{ $s->primarySurgeon?->name }}</td><td><x-ui.status-badge :status="$s->status" :label="__(\Illuminate\Support\Str::headline($s->status))" /></td></tr>@empty<tr><td colspan="5"><x-ui.empty-state :title="__('No cases are planned for today.')" icon="bi-scissors" /></td></tr>@endforelse</tbody></table></div></div></div>
        </div>
    </x-ui.dashboard>
@stop
