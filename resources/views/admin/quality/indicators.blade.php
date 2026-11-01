@extends('layouts.adminlte')

@section('page_title', __('Quality indicators'))

@section('page_content')
    @include('admin.quality._flash')
    <div class="d-flex mb-3"><p class="text-muted small mb-0">{{ __('Each figure is shown with how it is worked out. A month without enough to count shows a dash, never zero. Targets are the hospital\'s own to set.') }}</p>
        @if($canManage)<form method="POST" action="{{ route('admin.quality.indicators.recompute') }}" class="ms-auto">@csrf<button class="btn btn-sm btn-warning">{{ __('Work out again') }}</button></form>@endif</div>
    <div class="card"><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>{{ __('Indicator') }}</th><th class="text-end">{{ __('Latest') }}</th><th>{{ __('Month') }}</th><th class="text-end">{{ __('Before') }}</th><th>{{ __('Target') }}</th><th>{{ __('Against the target') }}</th><th>{{ __('Last months') }}</th></tr></thead><tbody>
        @foreach($rows as $r)@php($i = $r['indicator'])<tr><td><strong>{{ __($i->name) }}</strong><div class="small text-muted">{{ __($i->description) }}</div></td>
            <td class="text-end">{{ $r['value'] === null ? '—' : rtrim(rtrim(number_format($r['value'], 2), '0'), '.') }}{{ $i->unit === 'percent' ? '%' : '' }}<div class="small text-muted">{{ $i->unit === 'per_1000' ? __('per 1,000') : '' }}</div></td><td>{{ $r['period'] ?? '—' }}</td>
            <td class="text-end">{{ $r['previous'] === null ? '—' : rtrim(rtrim(number_format($r['previous'], 2), '0'), '.') }}</td>
            <td>@if($canManage)<form method="POST" action="{{ route('admin.quality.indicators.target', $i->public_id) }}" class="d-flex gap-1">@csrf<input name="target" type="number" step="any" min="0" class="form-control form-control-sm" style="width:90px" value="{{ $i->target !== null ? (float) $i->target : '' }}"><button class="btn btn-sm btn-outline-secondary">{{ __('Set') }}</button></form>@else{{ $i->target !== null ? (float) $i->target : '—' }}@endif</td>
            <td>@if($r['on_target'] === null)<span class="text-muted small">{{ __('no target') }}</span>@else<x-ui.status-badge :status="$r['on_target'] ? 'compliant' : 'non_compliant'" />@endif <span class="small text-muted">{{ $i->good_direction === 'down' ? __('lower is better') : __('higher is better') }}</span></td>
            <td class="small text-muted" style="font-variant-numeric: tabular-nums">{{ collect($r['series'])->map(fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format($v, 1), '0'), '.'))->implode(' · ') }}</td></tr>@endforeach
    </tbody></table></div></div>
@stop
