@extends('layouts.adminlte')

@section('page_title', __('KPIs and targets'))

@section('page_content')
    @include('admin.analytics._flash')
    @include('admin.analytics._range', ['from' => $from, 'to' => $to])
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('KPI') }}</th><th>{{ __('Area') }}</th><th class="text-end">{{ __('Now') }}</th><th class="text-end">{{ __('Period before') }}</th><th class="text-end">{{ __('Change') }}</th><th class="text-end">{{ __('Target') }}</th><th></th></tr></thead><tbody>
        @foreach($rows as ['kpi' => $k, 'r' => $r])
            @php($fmt = fn ($v) => \App\Support\AnalyticsFormat::value($v, $k->unit))
            <tr><td><a href="{{ route('admin.analytics.kpis.show', $k->public_id) }}">{{ __($k->name) }}</a> @if($k->organization_id)<span class="badge bg-info">{{ __('ours') }}</span>@endif <small class="text-muted">v{{ $k->version }}</small></td><td>{{ __(\Illuminate\Support\Str::headline($k->domain)) }}</td>
                <td class="text-end">{{ $r['value'] === null && $r['suppressed'] ? __('Hidden') : $fmt($r['value']) }}</td><td class="text-end">{{ $fmt($r['previous']) }}</td><td class="text-end">{{ $r['change_pct'] !== null ? ($r['change_pct'] >= 0 ? '+' : '').$r['change_pct'].'%' : '—' }}</td><td class="text-end">{{ $fmt($r['target']) }}</td>
                <td>@if($r['status'] === 'good')<i class="bi bi-check-circle text-success" title="{{ __('On target') }}"></i>@elseif($r['status'] === 'bad')<i class="bi bi-exclamation-circle text-danger" title="{{ __('Off target') }}"></i>@endif</td></tr>
        @endforeach</tbody></table></div></div>
    @can('analytics.kpi.manage')<p class="mt-3"><a class="btn btn-outline-primary" href="#own-kpi" onclick="document.getElementById('own-kpi').scrollIntoView()">{{ __('Make our own KPI') }}</a></p>
    @include('admin.analytics._kpi-form', ['catalogue' => \App\Services\Analytics\Semantic\SemanticCatalog::facts(), 'k' => null])@endcan
@stop
