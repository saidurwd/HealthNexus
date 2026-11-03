@extends('layouts.adminlte')

@section('page_title', __('National reporting (DGHS / DHIS2)'))

@section('page_content')
    @include('admin.analytics._flash')
    @php
        $withValue = collect($labels)->keys()->filter(fn ($k) => ($values[$k] ?? null) !== null)->count();
        $withCode = collect($labels)->keys()->filter(fn ($k) => ! empty($map['elements'][$k] ?? null))->count();
    @endphp
    <x-ui.dashboard :question="__('Is the monthly return to the national system ready to send?')" :audience="__('For the records officer and the director')" :as-of="\Carbon\CarbonImmutable::now()">
        <div class="row row-cols-2 row-cols-md-4 g-2 mb-3">
            <x-ui.kpi-tile :label="__('Indicators in the return')" :value="count($labels)" unit="count" icon="bi-list-check" variant="primary" good-direction="none" />
            <x-ui.kpi-tile :label="__('Indicators with a figure this month')" :value="$withValue" unit="count" icon="bi-123" variant="success" good-direction="up" :note="__('of :n', ['n' => count($labels)])" />
            <x-ui.kpi-tile :label="__('Indicators with a national code')" :value="$withCode" unit="count" icon="bi-upc-scan" :variant="$withCode < count($labels) ? 'warning' : 'success'" good-direction="up" :note="__('of :n', ['n' => count($labels)])" />
            <x-ui.kpi-tile :label="__('Ready to send')" :value="$dhis['ready'] ? __('Yes') : __('Not yet')" icon="bi-send-check" :variant="$dhis['ready'] ? 'success' : 'danger'" good-direction="none" :note="$dhis['ready'] ? null : __('Codes are set after onboarding with the national system.')" />
        </div>
    <div class="alert alert-warning small">{{ __('Monthly figures in the style of the national health information system. The codes the national system expects are set after onboarding with it; until then the file for the national system cannot be made. Check what is required before sending anything.') }}</div>
    <form method="GET" class="card card-body mb-3"><div class="row g-2 align-items-end"><div class="col-auto"><label class="form-label" for="month">{{ __('Month') }}</label><input id="month" type="month" name="month" value="{{ $month }}" class="form-control"></div><div class="col-auto"><button class="btn btn-primary">{{ __('Show') }}</button></div>
        @can('analytics.national.export')<div class="col-auto"><a class="btn btn-outline-secondary" href="{{ route('admin.analytics.national', ['month' => $month, 'export' => 'csv']) }}"><i class="bi bi-filetype-csv"></i> CSV</a> @if($dhis['ready'])<a class="btn btn-outline-primary" href="{{ route('admin.analytics.national', ['month' => $month, 'export' => 'dhis2']) }}"><i class="bi bi-filetype-json"></i> DHIS2</a>@else<button type="button" class="btn btn-outline-secondary" disabled title="{{ __('Set the codes first') }}">DHIS2</button>@endif</div>@endcan</div></form>
    <div class="card mb-3"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Indicator') }}</th><th class="text-end">{{ __('Value') }}</th><th>{{ __('National code') }}</th></tr></thead><tbody>
        @foreach($labels as $key => $label)<tr><td>{{ __($label) }}</td><td class="text-end">{{ $values[$key] ?? '—' }}</td><td class="small text-muted">{{ $map['elements'][$key] ?? __('not set') }}</td></tr>@endforeach</tbody></table></div>
    @can('analytics.national.manage')<form method="POST" action="{{ route('admin.analytics.national.save') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('National codes') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-6"><label class="form-label" for="data_set">{{ __('Data set') }}</label><input id="data_set" name="data_set" class="form-control" value="{{ $map['data_set'] }}"></div>
        <div class="col-md-6"><label class="form-label" for="org_unit">{{ __('Organisation unit (our hospital)') }}</label><input id="org_unit" name="org_unit" class="form-control" value="{{ $map['org_unit'] }}"></div>
        @foreach($labels as $key => $label)<div class="col-md-4"><label class="form-label" for="el-{{ $key }}">{{ __($label) }}</label><input id="el-{{ $key }}" name="elements[{{ $key }}]" class="form-control form-control-sm" value="{{ $map['elements'][$key] ?? '' }}"></div>@endforeach
    </div><div class="card-footer"><button class="btn btn-primary">{{ __('Save') }}</button></div></form>@endcan
    </x-ui.dashboard>
@stop
