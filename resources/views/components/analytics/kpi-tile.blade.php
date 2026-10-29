@props(['kpi', 'r'])
@php
    $unit = $kpi->unit;
    $fmt = fn ($v) => \App\Support\AnalyticsFormat::value($v, $unit);
    $border = ['good' => 'border-success', 'bad' => 'border-danger', 'neutral' => ''][$r['status']] ?? '';
    $goodUp = $kpi->good_direction === 'up';
    $change = $r['change_pct'];
    $changeGood = $change === null || $kpi->good_direction === 'none' ? null : (($change >= 0) === $goodUp);
@endphp
<div class="card h-100 {{ $border }}"><div class="card-body">
    <div class="text-muted small">{{ __($kpi->name) }}</div>
    <div class="fs-3"><a href="{{ route('admin.analytics.kpis.show', $kpi->public_id) }}" class="text-reset text-decoration-none">@if($r['value'] === null && $r['suppressed']){{ __('Hidden (small number)') }}@else{{ $fmt($r['value']) }}@endif</a></div>
    <div class="small">
        @if($change !== null)<span class="{{ $changeGood === null ? 'text-muted' : ($changeGood ? 'text-success' : 'text-danger') }}">{{ $change >= 0 ? '▲' : '▼' }} {{ abs($change) }}%</span> <span class="text-muted">{{ __('vs the period before') }}</span>@else<span class="text-muted">—</span>@endif
        @if($r['target'] !== null)<div class="text-muted">{{ __('Target') }}: {{ $fmt($r['target']) }} @if($r['status'] === 'good')<i class="bi bi-check-circle text-success" title="{{ __('On target') }}"></i>@elseif($r['status'] === 'bad')<i class="bi bi-exclamation-circle text-danger" title="{{ __('Off target') }}"></i>@endif</div>@endif
    </div>
</div></div>
