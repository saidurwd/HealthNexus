@extends('layouts.adminlte')

@section('page_title', __('Planning forecasts'))

@section('page_content')
    @include('admin.analytics._flash')
    <div class="alert alert-warning small">{{ __('A forecast is a rough guide for planning, built from the usual weekly pattern. It is advisory: nothing is ordered, priced or decided from it. The error shown is how far it was off on the last days it had not seen.') }}</div>
    <form method="GET" class="card card-body mb-3"><div class="row g-2 align-items-end"><div class="col-md-4"><label class="form-label" for="metric">{{ __('What') }}</label><select id="metric" name="metric" class="form-select" onchange="this.form.submit()">@foreach($metrics as $k => $l)<option value="{{ $k }}" @selected($metric === $k)>{{ __($l) }}</option>@endforeach</select></div>
        <div class="col-auto"><button class="btn btn-primary" formaction="{{ route('admin.analytics.forecast.make') }}" formmethod="POST">{{ __('Make a new forecast') }}</button>@csrf</div></div></form>
    @if($f)
        @php($hist = collect($f->points['history'])->map(fn ($v, $d) => ['date' => $d, 'actual' => $v])->values())
        @php($fut = collect($f->points['forecast'])->map(fn ($p, $d) => ['date' => $d, 'forecast' => $p['value'], 'low' => $p['low'], 'high' => $p['high']])->values())
        <p class="small text-muted">{{ __('Made') }} {{ $f->generated_at->setTimezone('Asia/Dhaka')->format('d M Y H:i') }} · {{ __($f->method) }} · {{ $f->error_pct !== null ? __('Error on the last days it had not seen') .': '.rtrim(rtrim((string) $f->error_pct, '0'), '.').'%' : __('Not enough history to judge its error') }}</p>
        <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Last 30 days and the next :n days', ['n' => $f->horizon_days]) }}</h3></div><div class="card-body"><x-analytics.chart :rows="$hist->concat($fut)->all()" x="date" :series="[['key' => 'actual', 'label' => 'What happened'], ['key' => 'forecast', 'label' => 'Forecast']]" kind="bar" /></div></div>
        <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Date') }}</th><th class="text-end">{{ __('Forecast') }}</th><th class="text-end">{{ __('Likely between') }}</th></tr></thead><tbody>@foreach($fut as $r)<tr><td>{{ $r['date'] }}</td><td class="text-end">{{ number_format($r['forecast'], 1) }}</td><td class="text-end">{{ number_format($r['low'], 1) }} – {{ number_format($r['high'], 1) }}</td></tr>@endforeach</tbody></table></div></div>
    @else<div class="text-muted">{{ __('No forecast yet. Press the button to make one.') }}</div>@endif
@stop
