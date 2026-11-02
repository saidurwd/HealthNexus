@props(['rows', 'x', 'series', 'kind' => 'bar', 'unit' => 'count', 'height' => 210, 'links' => [], 'title' => null])
{{--
    One chart for the whole product, drawn as inline SVG (no package, prints, works on a LAN). kind: bar (columns), line, hbar (a ranking, one series), spark (a tiny trend for a tile). Every chart has a title for screen readers,
    a table you can open, and series that differ by more than colour (dashes on lines, the legend names them). Figures are written the way the hospital chose (App\Support\Dashboard\Numbers).
--}}
@php
    $rows = array_values($rows);
    $n = count($rows);
    $colors = ['var(--bs-primary)', 'var(--bs-secondary)', 'var(--bs-success)'];
    $dashes = ['', '6 3', '2 3'];
    $label = fn ($v) => \App\Support\Dashboard\Numbers::value($v, $unit);
    $name = $title ?? collect($series)->pluck('label')->map(fn ($l) => __($l))->implode(', ');
    $vals = [];
    foreach ($rows as $r) { foreach ($series as $s) { $vals[] = (float) ($r[$s['key']] ?? 0); } }
    $max = max(1.0, ...($vals ?: [1.0]));
@endphp
@if($n === 0)
    <div class="text-muted small">{{ __('Nothing to show for this period.') }}</div>
@elseif($kind === 'spark')
    @php
        $w = 100; $h = 28; $min = min($vals); $span = max(1e-9, $max - $min); $step = $n > 1 ? ($w - 4) / ($n - 1) : 0;
    @endphp
    @php $pts = collect($rows)->map(fn ($r, $i) => round(2 + $step * $i, 1).','.round(2 + ($h - 4) * (1 - ((float) ($r[$series[0]['key']] ?? 0) - $min) / $span), 1)); @endphp
    <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-100" role="img" aria-label="{{ __('Trend') }}: {{ $name }}, {{ $label($vals[0]) }} → {{ $label($vals[count($vals) - 1]) }}"><title>{{ $name }}</title>
        <polyline points="{{ $pts->implode(' ') }}" fill="none" stroke="var(--bs-primary)" stroke-width="1.6"/>
        <circle cx="{{ explode(',', $pts->last())[0] }}" cy="{{ explode(',', $pts->last())[1] }}" r="2.2" fill="var(--bs-primary)"/>
    </svg>
@elseif($kind === 'hbar')
    @php $s0 = $series[0]; @endphp
    <div role="img" aria-label="{{ $name }}">
        @foreach($rows as $i => $r)
            @php $v = (float) ($r[$s0['key']] ?? 0); @endphp
            <div class="d-flex align-items-center gap-2 mb-1 small">
                <div class="text-truncate" style="width: 38%" title="{{ $r[$x] ?? '' }}">@if(! empty($links[$i]))<a href="{{ $links[$i] }}" class="text-reset">{{ $r[$x] ?? '' }}</a>@else{{ $r[$x] ?? '' }}@endif</div>
                <div class="flex-grow-1 bg-light rounded" style="height: 14px"><div class="rounded" style="height: 14px; width: {{ round(100 * $v / $max, 1) }}%; background: {{ $colors[0] }}"></div></div>
                <div class="text-end" style="width: 22%">{{ $label($v) }}</div>
            </div>
        @endforeach
    </div>
@else
    @php
        $w = 640; $h = (int) $height; $left = 46; $bottom = 26; $top = 10; $plotW = $w - $left - 8; $plotH = $h - $bottom - $top; $step = $n > 0 ? $plotW / $n : $plotW; $every = max(1, (int) ceil($n / 8));
    @endphp
    <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-100" role="img" aria-label="{{ $name }}" style="max-height: {{ $h }}px">
        <title>{{ $name }}</title>
        @foreach([0, 0.5, 1] as $g)
            @php $y = $top + $plotH - $plotH * $g; @endphp
            <line x1="{{ $left }}" x2="{{ $w - 8 }}" y1="{{ $y }}" y2="{{ $y }}" stroke="currentColor" stroke-opacity=".15"/>
            <text x="{{ $left - 4 }}" y="{{ $y + 3 }}" text-anchor="end" font-size="10" fill="currentColor" fill-opacity=".7">{{ $label($max * $g) }}</text>
        @endforeach
        @if($kind === 'line')
            @foreach($series as $si => $s)
                @php $pts = collect($rows)->map(fn ($r, $i) => round($left + $step * ($i + .5), 1).','.round($top + $plotH - $plotH * ((float) ($r[$s['key']] ?? 0)) / $max, 1))->implode(' '); @endphp
                <polyline points="{{ $pts }}" fill="none" stroke="{{ $colors[$si % 3] }}" stroke-width="2" @if($dashes[$si % 3] !== '') stroke-dasharray="{{ $dashes[$si % 3] }}" @endif/>
            @endforeach
            @foreach($rows as $i => $r)
                @foreach($series as $si => $s)
                    @php $v = (float) ($r[$s['key']] ?? 0); @endphp
                    <circle cx="{{ round($left + $step * ($i + .5), 1) }}" cy="{{ round($top + $plotH - $plotH * $v / $max, 1) }}" r="2.5" fill="{{ $colors[$si % 3] }}"><title>{{ $r[$x] ?? '' }}: {{ $label($v) }}</title></circle>
                @endforeach
            @endforeach
        @else
            @php $bw = max(2, $step / (count($series) + 1)); @endphp
            @foreach($rows as $i => $r)
                @if(! empty($links[$i]))<a href="{{ $links[$i] }}">@endif
                @foreach($series as $si => $s)
                    @php $v = (float) ($r[$s['key']] ?? 0); @endphp
                    @php $bh = $plotH * $v / $max; @endphp
                    <rect x="{{ round($left + $step * $i + $bw * ($si + .5), 1) }}" y="{{ round($top + $plotH - $bh, 1) }}" width="{{ round($bw * .9, 1) }}" height="{{ round(max(0, $bh), 1) }}" fill="{{ $colors[$si % 3] }}"><title>{{ $r[$x] ?? '' }} · {{ __($s['label']) }}: {{ $label($v) }}</title></rect>
                @endforeach
                @if(! empty($links[$i]))</a>@endif
            @endforeach
        @endif
        @foreach($rows as $i => $r)
            @if($i % $every === 0)<text x="{{ round($left + $step * ($i + .5), 1) }}" y="{{ $h - 8 }}" text-anchor="middle" font-size="10" fill="currentColor" fill-opacity=".7">{{ \Illuminate\Support\Str::limit((string) ($r[$x] ?? ''), 10, '') }}</text>@endif
        @endforeach
    </svg>
    @if(count($series) > 1)<div class="small text-muted">@foreach($series as $si => $s)<span class="me-3"><span style="display:inline-block;width:14px;height:0;border-top:3px {{ $kind === 'line' ? ($dashes[$si % 3] === '' ? 'solid' : 'dashed') : 'solid' }} {{ $colors[$si % 3] }};vertical-align:middle"></span> {{ __($s['label']) }}</span>@endforeach</div>@endif
@endif
@if($n > 0 && $kind !== 'spark')
    <details class="small mt-1"><summary class="text-muted">{{ __('Show as a table') }}</summary>
        <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th></th>@foreach($series as $s)<th class="text-end">{{ __($s['label']) }}</th>@endforeach</tr></thead><tbody>
            @foreach($rows as $r)<tr><td>{{ $r[$x] ?? '' }}</td>@foreach($series as $s)<td class="text-end">{{ $label((float) ($r[$s['key']] ?? 0)) }}</td>@endforeach</tr>@endforeach</tbody></table></div>
    </details>
@endif
