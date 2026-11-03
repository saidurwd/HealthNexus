@extends('layouts.adminlte')

@section('page_title', __('Quality and safety'))

@section('page_content')
    @include('admin.quality._flash')
    <div class="row g-2 mb-3">
        @can('quality.incident.report')<div class="col-6 col-md-3"><a class="btn btn-danger w-100" href="{{ route('admin.quality.incidents.create') }}"><i class="bi bi-flag"></i> {{ __('Report an incident') }}</a></div>
        <div class="col-6 col-md-3"><a class="btn btn-outline-secondary w-100" href="{{ route('admin.quality.incidents.mine') }}"><i class="bi bi-journal-text"></i> {{ __('My reports') }}</a></div>@endcan
        @can('quality.document.acknowledge')<div class="col-6 col-md-3"><a class="btn btn-outline-secondary w-100" href="{{ route('admin.quality.documents.toread') }}"><i class="bi bi-envelope-open"></i> {{ __('Documents to read') }} @if($toRead)<span class="badge text-bg-warning">{{ $toRead }}</span>@endif</a></div>@endcan
    </div>
    <p class="text-muted small">{{ __('Reporting is for learning, not for blame. You can report without your name; the quality team sees what happened, not who told them.') }}</p>

    @if($summary)
        <x-ui.dashboard :question="__('Are we learning from incidents, and are the corrective actions getting done?')" :audience="__('For the quality manager and the director')" :as-of="\Carbon\CarbonImmutable::now()">
        <div class="row row-cols-2 row-cols-md-4 row-cols-xl-5 g-2 mb-3">
            <x-ui.kpi-tile :label="__('Open incidents')" :value="$summary['incidents_open']" icon="bi-exclamation-triangle" variant="warning" :href="route('admin.quality.incidents')" />
            <x-ui.kpi-tile :label="__('Past their due date')" :value="$summary['incidents_overdue']" icon="bi-alarm" variant="danger" :href="route('admin.quality.incidents', ['overdue' => 1])" />
            <x-ui.kpi-tile :label="__('Serious in the last 90 days')" :value="$summary['serious_90d']" icon="bi-activity" variant="danger" />
            <x-ui.kpi-tile :label="__('Actions open')" :value="$summary['actions_open']" icon="bi-list-check" variant="primary" :href="route('admin.quality.actions', ['show' => 'open'])" />
            <x-ui.kpi-tile :label="__('Actions overdue')" :value="$summary['actions_overdue']" icon="bi-alarm" variant="danger" :href="route('admin.quality.actions', ['show' => 'overdue'])" />
            <x-ui.kpi-tile :label="__('Actions waiting to be checked')" :value="$summary['actions_to_verify']" icon="bi-patch-check" variant="info" :href="route('admin.quality.actions', ['show' => 'verify'])" />
            <x-ui.kpi-tile :label="__('Outbreaks open')" :value="$summary['outbreaks_open']" icon="bi-virus" variant="danger" :href="route('admin.quality.infection')" />
            <x-ui.kpi-tile :label="__('Patients in isolation')" :value="$summary['isolations_active']" icon="bi-shield-exclamation" variant="warning" :href="route('admin.quality.infection')" />
            <x-ui.kpi-tile :label="__('Deaths to review')" :value="$summary['deaths_to_review']" icon="bi-heart-pulse" variant="secondary" :href="route('admin.quality.mortality')" />
            <x-ui.kpi-tile :label="__('Documents waiting for approval')" :value="$summary['documents_in_review']" icon="bi-file-earmark-check" variant="info" :href="route('admin.quality.documents')" />
            <x-ui.kpi-tile :label="__('Privileges waiting')" :value="$summary['privileges_waiting']" icon="bi-person-badge" variant="info" :href="route('admin.quality.privileges', ['status' => 'requested'])" />
            <x-ui.kpi-tile :label="__('Licences and inspections due soon')" :value="$summary['compliance_late']" icon="bi-calendar-check" variant="warning" :href="route('admin.quality.compliance')" />
        </div>
        <div class="row g-3 mb-3">
            <div class="col-lg-7"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Reports by month') }}</h3></div><div class="card-body">
                <x-analytics.chart :rows="$summary['trend']" x="month" :series="[['key' => 'all', 'label' => 'All reports'], ['key' => 'near_miss', 'label' => 'Near misses']]" kind="line" unit="count" />
            </div></div></div>
            <div class="col-lg-5"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('What is reported') }}</h3></div>
                <ul class="list-group list-group-flush small">@forelse($summary['by_category'] as $cat => $n)<li class="list-group-item d-flex justify-content-between">{{ __(\Illuminate\Support\Str::headline($cat)) }}<span class="badge text-bg-secondary">{{ $n }}</span></li>@empty<li class="list-group-item text-muted">{{ __('Nothing reported yet.') }}</li>@endforelse</ul></div></div>
        </div>
        @can('quality.indicator.view')
        <div class="card"><div class="card-header d-flex"><h3 class="card-title">{{ __('Quality indicators') }}</h3><a class="ms-auto small" href="{{ route('admin.quality.indicators') }}">{{ __('All indicators') }}</a></div>
            <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Indicator') }}</th><th class="text-end">{{ __('Latest') }}</th><th>{{ __('Month') }}</th><th>{{ __('Against the target') }}</th></tr></thead><tbody>
            @foreach(array_slice($summary['indicators'], 0, 8) as $r)<tr><td>{{ __($r['indicator']->name) }}</td><td class="text-end">{{ $r['value'] === null ? '—' : \App\Support\AnalyticsFormat::value($r['value'], $r['indicator']->unit === 'per_1000' ? 'decimal' : ($r['indicator']->unit === 'percent' ? 'percent' : 'count')) }}</td><td>{{ $r['period'] ?? '—' }}</td>
                <td>@if($r['on_target'] === null)<span class="text-muted small">{{ __('no target') }}</span>@else<x-ui.status-badge :status="$r['on_target'] ? 'compliant' : 'non_compliant'" />@endif</td></tr>@endforeach
            </tbody></table></div></div>
        @endcan
        </x-ui.dashboard>
    @endif
@stop
