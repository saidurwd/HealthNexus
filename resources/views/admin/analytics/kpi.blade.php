@extends('layouts.adminlte')

@section('page_title', __($k->name))

@section('page_content')
    @include('admin.analytics._flash')
    @php($fmt = fn ($v) => \App\Support\AnalyticsFormat::value($v, $k->unit))
    @include('admin.analytics._range', ['from' => $from, 'to' => $to])
    <div class="row g-3 mb-3">
        <div class="col-md-4"><x-analytics.kpi-tile :kpi="$k" :r="$r" /></div>
        <div class="col-md-8"><div class="card h-100"><div class="card-body small">
            <p>{{ __($k->description) }}</p>
            <div>{{ __('Area') }}: {{ __(\Illuminate\Support\Str::headline($k->domain)) }} · {{ __('Good is') }}: {{ ['up' => __('higher'), 'down' => __('lower'), 'none' => __('neither')][$k->good_direction] }} · {{ __('Version') }} {{ $k->version }} · {{ $k->organization_id ? __('our own KPI') : __('standard KPI') }}</div>
            <div class="text-muted">{{ __('Defined as') }}: {{ $k->definition['fact'] }} → {{ $k->definition['measure'] }}@if(! empty($k->definition['filters'])) ({{ collect($k->definition['filters'])->map(fn ($f) => $f['dimension'].' '.$f['op'].' '.(is_array($f['value']) ? implode(',', $f['value']) : $f['value']))->implode(', ') }})@endif</div>
            @can('analytics.kpi.manage')<form method="POST" action="{{ route('admin.analytics.kpis.target', $k->public_id) }}" class="d-flex gap-2 mt-2 align-items-center">@csrf<label for="target" class="mb-0">{{ __('Our target') }}</label><input id="target" name="target" class="form-control form-control-sm" style="width:8rem" value="{{ $target }}"><button class="btn btn-sm btn-outline-primary">{{ __('Save target') }}</button></form>@endcan
        </div></div></div>
    </div>
    <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Last twelve months') }}</h3></div><div class="card-body">
        <x-analytics.chart :rows="$trend['rows']" x="period" :series="[['key' => $measure, 'label' => $k->name]]" kind="line" :unit="$k->unit" /></div></div>
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Versions of this KPI') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Version') }}</th><th>{{ __('Name') }}</th><th>{{ __('Target') }}</th><th>{{ __('Changed') }}</th><th>{{ __('Why') }}</th></tr></thead><tbody>
        @foreach($versions as $v)<tr><td>{{ $v->version }}</td><td>{{ $v->name }}</td><td>{{ $fmt($v->target) }}</td><td>{{ $v->changed_at?->setTimezone('Asia/Dhaka')->format('d M Y H:i') }}</td><td>{{ $v->note }}</td></tr>@endforeach</tbody></table></div></div>
    @can('analytics.kpi.manage')@if($k->organization_id)@include('admin.analytics._kpi-form', ['catalogue' => $catalogue, 'k' => $k])
        <form method="POST" action="{{ route('admin.analytics.kpis.retire', $k->public_id) }}" class="mt-2" onsubmit="return confirm('{{ __('Retire this KPI?') }}')">@csrf<button class="btn btn-sm btn-outline-danger">{{ __('Retire this KPI') }}</button></form>@endif @endcan
@stop
