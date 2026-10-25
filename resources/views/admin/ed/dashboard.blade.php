@extends('layouts.adminlte')

@section('page_title', __('Emergency performance'))

@section('page_content')
    <form method="GET" class="d-flex gap-2 mb-3 align-items-center">
        <label class="mb-0" for="days">{{ __('Period') }}</label>
        <select name="days" id="days" class="form-select w-auto" onchange="this.form.submit()">
            @foreach([1 => 'Last 24 hours', 7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'] as $value => $label)<option value="{{ $value }}" @selected($days === $value)>{{ __($label) }}</option>@endforeach
        </select>
    </form>

    <div class="row g-3 mb-3">
        @php($tiles = [
            [__('Arrivals'), $kpi['arrivals'], null],
            [__('In the department now'), $kpi['active_now'], null],
            [__('Door to triage (median / 90%)'), $kpi['door_to_triage'] ? $kpi['door_to_triage']['median'].' / '.$kpi['door_to_triage']['p90'].' '.__('min') : '—', null],
            [__('Door to doctor (median / 90%)'), $kpi['door_to_doctor'] ? $kpi['door_to_doctor']['median'].' / '.$kpi['door_to_doctor']['p90'].' '.__('min') : '—', null],
            [__('Seen within target'), $kpi['door_to_doctor_met_percent'] !== null ? $kpi['door_to_doctor_met_percent'].' %' : '—', null],
            [__('Length of stay (median / 90%)'), $kpi['length_of_stay'] ? $kpi['length_of_stay']['median'].' / '.$kpi['length_of_stay']['p90'].' '.__('min') : '—', null],
            [__('Left without being seen'), $kpi['lwbs_percent'].' %', null],
        ])
        @foreach($tiles as [$label, $value])
            <div class="col-6 col-md-4 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ $label }}</div><div class="fs-3 fw-semibold">{{ $value }}</div></div></div></div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-md-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Arrivals by triage level') }}</h3></div>
            <ul class="list-group list-group-flush">@forelse($kpi['by_level'] as $level => $total)<li class="list-group-item d-flex justify-content-between">{{ $level === 'untriaged' ? __('Not triaged') : __('Level').' '.$level }}<strong>{{ $total }}</strong></li>@empty<li class="list-group-item text-muted">{{ __('No data for this period.') }}</li>@endforelse</ul></div></div>
        <div class="col-md-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Outcomes') }}</h3></div>
            <ul class="list-group list-group-flush">@forelse($kpi['by_disposition'] as $type => $total)<li class="list-group-item d-flex justify-content-between">{{ __(\Illuminate\Support\Str::headline($type)) }}<strong>{{ $total }}</strong></li>@empty<li class="list-group-item text-muted">{{ __('No data for this period.') }}</li>@endforelse</ul></div></div>
    </div>
@stop
