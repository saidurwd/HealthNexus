@extends('layouts.adminlte')

@section('page_title', __('Compare with other hospitals'))

@section('page_content')
    @include('admin.analytics._flash')
    <div class="alert alert-info small">{{ __('Only hospitals that agree take part. Other hospitals are never named: you see where you stand against the middle of the others. Nothing is shown until at least :n other hospitals take part.', ['n' => $min]) }}</div>
    @can('analytics.benchmark.manage')<form method="POST" action="{{ route('admin.analytics.benchmark.optin') }}" class="mb-3">@csrf<input type="hidden" name="in" value="{{ $in ? 0 : 1 }}"><button class="btn {{ $in ? 'btn-outline-secondary' : 'btn-primary' }}">{{ $in ? __('Stop taking part') : __('Take part in the comparison') }}</button></form>@endcan
    @if($in)
        @include('admin.analytics._range', ['from' => $from, 'to' => $to])
        <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('KPI') }}</th><th class="text-end">{{ __('Ours') }}</th><th class="text-end">{{ __('Others: lower quarter ends at') }}</th><th class="text-end">{{ __('Middle') }}</th><th class="text-end">{{ __('Upper quarter starts at') }}</th><th>{{ __('Where we stand') }}</th></tr></thead><tbody>
            @foreach($rows as ['kpi' => $k, 'b' => $b])@php($fmt = fn ($v) => \App\Support\AnalyticsFormat::value($v, $k->unit))
                <tr><td>{{ __($k->name) }}</td><td class="text-end">{{ $fmt($b['own']) }}</td>@if($b['available'])<td class="text-end">{{ $fmt($b['p25']) }}</td><td class="text-end">{{ $fmt($b['median']) }}</td><td class="text-end">{{ $fmt($b['p75']) }}</td><td>{{ __($b['position']) }} <small class="text-muted">({{ $b['peers'] }} {{ __('hospitals') }})</small></td>@else<td colspan="4" class="text-muted small">{{ __($b['reason']) }}</td>@endif</tr>@endforeach</tbody></table></div></div>
    @endif
@stop
