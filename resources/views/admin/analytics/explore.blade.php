@extends('layouts.adminlte')

@section('page_title', __('Explore the figures'))

@section('page_content')
    @include('admin.analytics._flash')
    @php($f = $catalogue[$q['fact']])
    <form method="GET" class="card mb-3"><div class="card-body row g-2">
        <div class="col-md-3"><label class="form-label" for="fact">{{ __('Subject') }}</label><select id="fact" name="fact" class="form-select" onchange="this.form.submit()">@foreach($catalogue as $key => $c)<option value="{{ $key }}" @selected($q['fact'] === $key)>{{ __($c['label']) }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="measures">{{ __('Figures') }}</label><select id="measures" name="measures[]" class="form-select" multiple size="5">@foreach($f['measures'] as $mk => $m)<option value="{{ $mk }}" @selected(in_array($mk, $q['measures'], true))>{{ __($m['label']) }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="group_by">{{ __('Cut by (up to two)') }}</label><select id="group_by" name="group_by[]" class="form-select" multiple size="5">@foreach($f['dimensions'] as $dk => $d)<option value="{{ $dk }}" @selected(in_array($dk, $q['group_by'], true))>{{ __($d['label']) }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="grain">{{ __('Over time') }}</label><select id="grain" name="grain" class="form-select"><option value="">{{ __('Total') }}</option>@foreach(['day' => 'By day', 'month' => 'By month', 'year' => 'By year'] as $gk => $gl)<option value="{{ $gk }}" @selected($q['grain'] === $gk)>{{ __($gl) }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label" for="from">{{ __('From') }}</label><input type="date" id="from" name="from" value="{{ $from }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label" for="to">{{ __('To') }}</label><input type="date" id="to" name="to" value="{{ $to }}" class="form-control"></div>
        @php($flt = $q['filters'][0] ?? null)
        <div class="col-md-3"><label class="form-label" for="fd0">{{ __('Only where') }}</label><select id="fd0" name="fd[]" class="form-select"><option value=""></option>@foreach($f['dimensions'] as $dk => $d)<option value="{{ $dk }}" @selected(($flt['dimension'] ?? '') === $dk)>{{ __($d['label']) }}</option>@endforeach</select></div>
        <div class="col-md-1"><label class="form-label" for="fo0">{{ __('is') }}</label><select id="fo0" name="fo[]" class="form-select">@foreach(['=' => '=', '!=' => '≠', 'in' => 'in', '>' => '>', '<' => '<'] as $ok => $ol)<option value="{{ $ok }}" @selected(($flt['op'] ?? '=') === $ok)>{{ $ol }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label" for="fv0">{{ __('value') }}</label><input id="fv0" name="fv[]" class="form-control" value="{{ is_array($flt['value'] ?? null) ? implode(',', $flt['value']) : ($flt['value'] ?? '') }}"></div>
        <div class="col-12"><button class="btn btn-primary">{{ __('Show') }}</button>
            @if($result && auth()->user()->can('analytics.report.export'))<a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"><i class="bi bi-download"></i> {{ __('Download') }}</a>@endif</div>
    </div></form>
    @if($error)<div class="alert alert-warning">{{ $error }}</div>@endif
    @if($result)
        @php($cols = collect($result['columns']))
        @php($dim = $cols->firstWhere('kind', 'dimension'))
        @php($measures = $cols->where('kind', 'measure')->values())
        @if($result['meta']['note'] ?? false)<div class="alert alert-info">{{ __($result['meta']['note']) }}</div>@endif
        @if($dim && count($result['rows']) > 1)<div class="card mb-3"><div class="card-body"><x-analytics.chart :rows="$result['rows']" :x="$dim['key']" :series="$measures->take(2)->map(fn ($m) => ['key' => $m['key'], 'label' => $m['label']])->all()" :kind="$q['grain'] ? 'line' : 'bar'" :unit="$measures[0]['unit']" /></div></div>@endif
        <div class="card mb-3"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr>@foreach($cols as $c)<th class="{{ $c['kind'] === 'measure' ? 'text-end' : '' }}">{{ __($c['label']) }}</th>@endforeach</tr></thead><tbody>
            @forelse($result['rows'] as $r)<tr>@foreach($cols as $c)<td class="{{ $c['kind'] === 'measure' ? 'text-end' : '' }}">{{ $c['kind'] === 'measure' ? \App\Support\AnalyticsFormat::value($r[$c['key']] ?? null, $c['unit']) : ($r[$c['key']] ?? '—') }}</td>@endforeach</tr>@empty<tr><td colspan="{{ $cols->count() }}" class="text-muted">{{ __('Nothing for this period.') }}</td></tr>@endforelse</tbody></table></div>
            @if($result['meta']['suppressed'] > 0)<div class="card-footer small text-muted">{{ __('Small numbers are hidden.') }}</div>@endif</div>
        @if($result['meta']['sql'] !== '')<details class="mb-3"><summary class="small text-muted">{{ __('How this was worked out (the query that ran)') }}</summary><pre class="small bg-body-tertiary p-2 mt-2" style="white-space:pre-wrap">{{ $result['meta']['sql'] }}</pre></details>@endif
        @can('analytics.report.manage')<form method="POST" action="{{ route('admin.analytics.reports.store') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Save this as a report') }}</h3></div><div class="card-body row g-2">@csrf
            <input type="hidden" name="query" value="{{ json_encode(\Illuminate\Support\Arr::only($q, ['fact', 'measures', 'group_by', 'grain', 'filters', 'order_by', 'direction', 'limit'])) }}">
            <div class="col-md-4"><label class="form-label" for="rn">{{ __('Name') }}</label><input id="rn" name="name" class="form-control" required maxlength="120"></div>
            <div class="col-md-6"><label class="form-label" for="rd">{{ __('Description') }}</label><input id="rd" name="description" class="form-control" maxlength="400"></div>
            <div class="col-md-2 d-flex align-items-end"><input type="hidden" name="shared" value="0"><div class="form-check me-2"><input type="checkbox" class="form-check-input" id="rs" name="shared" value="1"><label for="rs" class="form-check-label">{{ __('Share') }}</label></div><button class="btn btn-primary">{{ __('Save') }}</button></div></div></form>@endcan
    @endif
@stop
