{{-- One dashboard widget. $w = a rendered widget (see DashboardService::render). --}}
@if(! empty($w['error']))
    <div class="card h-100 border-warning"><div class="card-body small text-warning">{{ $w['title'] ?? __('Widget') }}: {{ $w['error'] }}</div></div>
@elseif($w['type'] === 'kpi')
    <x-analytics.kpi-tile :kpi="$w['kpi']" :r="$w['result']" />
@else
    @php($data = $w['data'])
    @php($cols = collect($data['columns']))
    @php($dim = $cols->firstWhere('kind', 'dimension'))
    @php($measures = $cols->where('kind', 'measure')->values())
    <div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __($w['title'] ?: ($measures[0]['label'] ?? '')) }}</h3><div class="card-tools"><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.analytics.explore', array_merge(\Illuminate\Support\Arr::only($w['run_query'], ['fact', 'grain', 'from', 'to', 'order_by', 'direction']), ['measures' => $w['run_query']['measures'], 'group_by' => $w['run_query']['group_by'] ?? []])) }}" title="{{ __('Open in the explorer') }}"><i class="bi bi-zoom-in"></i></a></div></div>
    <div class="card-body">
        @if($w['type'] === 'chart')
            @php($rows = $data['rows'])
            @php($links = ($dim && $dim['key'] !== 'period') ? collect($rows)->map(fn ($r) => route('admin.analytics.explore', ['fact' => $w['run_query']['fact'], 'measures' => $w['run_query']['measures'], 'grain' => 'day', 'fd' => [$dim['key']], 'fo' => ['='], 'fv' => [$r[$dim['key'].'_id'] ?? $r[$dim['key']] ?? ''], 'from' => $data['meta']['from'], 'to' => $data['meta']['to']]))->all() : [])
            <x-analytics.chart :rows="$rows" :x="$dim['key'] ?? 'period'" :series="$measures->map(fn ($m) => ['key' => $m['key'], 'label' => $m['label']])->all()" :kind="$w['kind'] ?? 'bar'" :unit="$measures[0]['unit'] ?? 'count'" :links="$links" />
        @else
            <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr>@foreach($cols as $c)<th class="{{ $c['kind'] === 'measure' ? 'text-end' : '' }}">{{ __($c['label']) }}</th>@endforeach</tr></thead><tbody>
                @forelse($data['rows'] as $r)<tr>@foreach($cols as $c)<td class="{{ $c['kind'] === 'measure' ? 'text-end' : '' }}">{{ $c['kind'] === 'measure' ? \App\Support\AnalyticsFormat::value($r[$c['key']] ?? null, $c['unit']) : ($r[$c['key']] ?? '—') }}</td>@endforeach</tr>@empty<tr><td colspan="{{ $cols->count() }}" class="text-muted">{{ __('Nothing to show for this period.') }}</td></tr>@endforelse</tbody></table></div>
        @endif
        @if(($data['meta']['suppressed'] ?? 0) > 0)<div class="small text-muted mt-1">{{ __('Small numbers are hidden.') }}</div>@endif
    </div></div>
@endif
