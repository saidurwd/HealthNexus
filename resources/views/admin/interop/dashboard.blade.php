@extends('layouts.adminlte')

@section('page_title', __('Connections to other systems'))

@section('page_content')
    @include('admin.interop._flash')
    @php($tiles = [
        [__('Partners switched on'), $d['partners']['active'], false, route('admin.interop.partners')],
        [__('Messages waiting or retrying'), $d['queue_depth'], false, route('admin.interop.messages', ['status' => 'queued'])],
        [__('Failed for good (dead-letter list)'), $d['dead'], $d['dead'] > 0, route('admin.interop.messages', ['status' => 'dead'])],
        [__('Results from outside waiting for a person'), $d['results_to_review'], $d['results_to_review'] > 0, route('admin.interop.results')],
        [__('Patients not shared because they did not agree (7 days)'), $d['denied_7d'], false, route('admin.interop.access-log', ['outcome' => 'denied_consent'])],
        [__('Emergency overrides (7 days)'), $d['overrides_7d'], $d['overrides_7d'] > 0, route('admin.interop.access-log', ['outcome' => 'override'])],
        [__('Records read by partners (7 days)'), $d['fhir_reads_7d'], false, route('admin.interop.access-log')],
        [__('Scanner worklist waiting'), $d['worklist']['scheduled'] ?? 0, false, route('admin.interop.worklist')],
        [__('Seconds from queued to delivered (average)'), $d['avg_delivery_seconds'] !== null ? round($d['avg_delivery_seconds']) : '—', false, null],
    ])
    <div class="row g-3 mb-3">@foreach($tiles as [$label, $value, $warn, $url])<div class="col-6 col-md-4 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-semibold {{ $warn ? 'text-danger' : '' }}">@if($url)<a href="{{ $url }}" class="{{ $warn ? 'text-danger' : '' }}">{{ $value }}</a>@else{{ $value }}@endif</div></div></div></div>@endforeach</div>
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('How each partner is doing') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Partner') }}</th><th>{{ __('Kind') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Last 7 days') }}</th><th class="text-end">{{ __('Waiting') }}</th><th class="text-end">{{ __('Dead') }}</th><th>{{ __('Last delivered') }}</th><th>{{ __('Last problem') }}</th></tr></thead>
        <tbody>@forelse($d['health'] as $h)<tr><td><a href="{{ route('admin.interop.partners.show', $h['partner']->public_id) }}">{{ $h['partner']->name }}</a></td><td>{{ __(\Illuminate\Support\Str::headline($h['partner']->kind)) }}</td><td><x-ui.status-badge :status="$h['partner']->status" /></td><td class="text-end">{{ $h['week'] }}</td><td class="text-end">{{ $h['queued'] }}</td><td class="text-end {{ $h['dead'] ? 'text-danger' : '' }}">{{ $h['dead'] }}</td><td>{{ $h['last_ok'] ? \Illuminate\Support\Carbon::parse($h['last_ok'])->diffForHumans() : '—' }}</td><td>{{ $h['last_failure'] ? \Illuminate\Support\Carbon::parse($h['last_failure'])->diffForHumans() : '—' }}</td></tr>
        @empty<tr><td colspan="8" class="text-muted">{{ __('No partners yet.') }}</td></tr>@endforelse</tbody></table></div></div>
    <div class="row g-3"><div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Sent, last 7 days') }}</h3></div><ul class="list-group list-group-flush small">@forelse($d['out'] as $s => $n)<li class="list-group-item d-flex justify-content-between"><x-ui.status-badge :status="$s" /><strong>{{ $n }}</strong></li>@empty<li class="list-group-item text-muted">{{ __('Nothing yet.') }}</li>@endforelse</ul></div></div>
        <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Received, last 7 days') }}</h3></div><ul class="list-group list-group-flush small">@forelse($d['in'] as $s => $n)<li class="list-group-item d-flex justify-content-between"><x-ui.status-badge :status="$s" /><strong>{{ $n }}</strong></li>@empty<li class="list-group-item text-muted">{{ __('Nothing yet.') }}</li>@endforelse</ul></div></div></div>
@stop
