@props(['label', 'value', 'icon' => 'bi-activity', 'variant' => 'primary', 'href' => null, 'unit' => null, 'previous' => null, 'goodDirection' => 'up', 'target' => null, 'spark' => [], 'note' => null, 'suppressed' => false, 'compareLabel' => null])
{{--
    The headline figure of a dashboard (docs/ux/dashboard-standard.md). With only label and value it is the plain tile. Give it a `unit` (count, money, taka, percent, minutes, hours, days) and it writes the figure the way the hospital
    chose, and with `previous` it shows the change against the period before, with `target` whether it is met (colour + icon + word), with `spark` a small trend. `href` makes the whole tile open the list the figure counts.
--}}
@php
    $numeric = $unit !== null && is_numeric($value) && ! $suppressed;
    $shown = $suppressed ? __('Hidden (small number)') : ($numeric ? \App\Support\Dashboard\Numbers::value($value, $unit) : $value);
    $change = $numeric ? \App\Support\Dashboard\Kpi::change($value, $previous) : null;
    $good = \App\Support\Dashboard\Kpi::changeIsGood($change, $goodDirection);
    $status = $numeric ? \App\Support\Dashboard\Kpi::status($value, $target, $goodDirection) : \App\Support\Dashboard\Kpi::status(null, null);
    $border = ['good' => 'border-success', 'warn' => 'border-warning', 'bad' => 'border-danger'][$status['state']] ?? '';
@endphp
<div class="col">
    <div class="card h-100 {{ $border }}">
        <div class="card-body d-flex align-items-center gap-3">
            <i class="bi {{ $icon }} fs-2 text-{{ $variant }}" aria-hidden="true"></i>
            <div class="flex-grow-1">
                <div class="h4 mb-0">{{ $shown }}</div>
                @if($href)<a href="{{ $href }}" class="small stretched-link text-decoration-none text-muted">{{ $label }}</a>@else<span class="small text-muted">{{ $label }}</span>@endif
                @if($change !== null)
                    <div class="small {{ $good === null ? 'text-muted' : ($good ? 'text-success' : 'text-danger') }}">
                        <span aria-hidden="true">{{ $change >= 0 ? '▲' : '▼' }}</span> {{ \App\Support\Dashboard\Numbers::digits(($change >= 0 ? '+' : '−').abs($change).'%') }}
                        <span class="text-muted">{{ $compareLabel ?? __('vs the period before') }}</span>
                    </div>
                @endif
                @if($status['state'] !== 'neutral')
                    <div class="small text-{{ $status['class'] }}"><i class="bi {{ $status['icon'] }}" aria-hidden="true"></i> {{ $status['word'] }} <span class="text-muted">({{ __('Target') }}: {{ \App\Support\Dashboard\Numbers::value($target, $unit) }})</span></div>
                @endif
                @if($note)<div class="small text-muted">{{ $note }}</div>@endif
            </div>
            @if(is_array($spark) && count($spark) > 1)
                <div class="ms-auto" style="width: 90px"><x-ui.chart kind="spark" :rows="array_map(fn ($v) => ['v' => $v], array_values($spark))" x="v" :series="[['key' => 'v', 'label' => $label]]" :unit="$unit ?? 'count'" /></div>
            @endif
        </div>
    </div>
</div>
