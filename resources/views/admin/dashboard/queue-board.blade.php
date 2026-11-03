@extends('layouts.adminlte')

@section('page_title', __('Outpatient queue'))

@section('page_content')
    <x-ui.dashboard :question="__('How long are people waiting, and which doctors are busy?')" :audience="__('For reception. Token numbers only, so it can be put on a wall.')" :as-of="$asOf" :refresh="$refresh">
        <div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-2 mb-3">
            <x-ui.kpi-tile :label="__('Waiting now')" :value="$s['waiting']" unit="count" icon="bi-people" variant="primary" :href="route('admin.queue.index')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Longest wait')" :value="$s['longest']" unit="minutes" icon="bi-hourglass-split" variant="warning" good-direction="down" :target="app(\App\Services\Dashboard\Targets::class)->get('opd_longest_wait')" />
            <x-ui.kpi-tile :label="__('Average wait before being called')" :value="$s['average_served']" unit="minutes" icon="bi-stopwatch" variant="info" good-direction="down" :note="$s['served_count'] > 0 ? __('over :n patients called today', ['n' => \App\Support\Dashboard\Numbers::number($s['served_count'])]) : __('No one has been called yet today.')" />
            <x-ui.kpi-tile :label="__('Seen today')" :value="$s['seen']" unit="count" icon="bi-person-check" variant="success" good-direction="up" />
            <x-ui.kpi-tile :label="__('Did not come')" :value="$s['no_shows']" unit="count" icon="bi-person-x" variant="secondary" good-direction="down" />
            <x-ui.kpi-tile :label="__('Doctors with patients')" :value="$s['doctors_busy']" unit="count" icon="bi-person-badge" variant="primary" good-direction="none" />
        </div>
        <div class="row g-3">
            <div class="col-lg-8"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('By doctor') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Doctor') }}</th><th class="text-end">{{ __('Waiting') }}</th><th class="text-end">{{ __('With the doctor') }}</th><th class="text-end">{{ __('Done') }}</th><th class="text-end">{{ __('Longest wait') }}</th><th>{{ __('Next token') }}</th></tr></thead><tbody>
                @forelse($s['by_doctor'] as $d)<tr><td>{{ $d['doctor'] }}</td><td class="text-end">{{ \App\Support\Dashboard\Numbers::number($d['waiting']) }}</td><td class="text-end">{{ \App\Support\Dashboard\Numbers::number($d['in_consultation']) }}</td><td class="text-end">{{ \App\Support\Dashboard\Numbers::number($d['done']) }}</td><td class="text-end">{{ \App\Support\Dashboard\Numbers::value($d['longest'], 'minutes') }}</td><td>{{ $d['next'] ?? '—' }}</td></tr>@empty<tr><td colspan="6"><x-ui.empty-state :title="__('No one is booked or waiting today.')" :hint="__('Patients appear here when they are checked in.')" icon="bi-people" /></td></tr>@endforelse</tbody></table></div></div></div>
            <div class="col-lg-4"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('When the people waiting arrived') }}</h3></div><div class="card-body">
                @if(count($s['waiting_by_hour']) > 0)<x-ui.chart kind="bar" :rows="$s['waiting_by_hour']" x="hour" :series="[['key' => 'n', 'label' => 'Waiting']]" :height="170" />@else<x-ui.empty-state :title="__('No one is waiting.')" icon="bi-check2-circle" />@endif
            </div></div></div>
        </div>
    </x-ui.dashboard>
@stop
