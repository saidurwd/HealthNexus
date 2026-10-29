@props(['rows', 'x', 'series', 'kind' => 'bar', 'unit' => 'count', 'height' => 210, 'links' => []])
@php
    $rows = array_values($rows);
    $n = count($rows);
    $w = 640; $h = (int) $height; $left = 46; $bottom = 26; $top = 10; $plotW = $w - $left - 8; $plotH = $h - $bottom - $top;
    $vals = [];
    foreach ($rows as $r) { foreach ($series as $s) { $vals[] = (float) ($r[$s['key']] ?? 0); } }
    $max = max(1.0, ...($vals ?: [1.0]));
    $colors = ['var(--bs-primary)', 'var(--bs-secondary)', 'var(--bs-success)'];
    $step = $n > 0 ? $plotW / $n : $plotW;
    $label = fn ($v) => \App\Support\AnalyticsFormat::value($v, $unit);
    $every = max(1, (int) ceil($n / 8));
@endphp
@if($n === 0)
    <div class="text-muted small">{{ __('Nothing to show for this period.') }}</div>
@else
<svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-100" role="img" aria-label="{{ collect($series)->pluck('label')->map(fn ($l) => __($l))->implode(', ') }}" style="max-height: {{ $h }}px">
    <title>{{ collect($series)->pluck('label')->map(fn ($l) => __($l))->implode(', ') }}</title>
    @foreach([0, 0.5, 1] as $g)
        @php($y = $top + $plotH - $plotH * $g)
        <line x1="{{ $left }}" x2="{{ $w - 8 }}" y1="{{ $y }}" y2="{{ $y }}" stroke="currentColor" stroke-opacity=".15"/>
        <text x="{{ $left - 4 }}" y="{{ $y + 3 }}" text-anchor="end" font-size="10" fill="currentColor" fill-opacity=".7">{{ $label($max * $g) }}</text>
    @endforeach
    @if($kind === 'line')
        @foreach($series as $si => $s)
            @php($pts = collect($rows)->map(fn ($r, $i) => round($left + $step * ($i + .5), 1).','.round($top + $plotH - $plotH * ((float) ($r[$s['key']] ?? 0)) / $max, 1))->implode(' '))
            <polyline points="{{ $pts }}" fill="none" stroke="{{ $colors[$si % 3] }}" stroke-width="2"/>
        @endforeach
        @foreach($rows as $i => $r)
            @foreach($series as $si => $s)
                @php($v = (float) ($r[$s['key']] ?? 0))
                <circle cx="{{ round($left + $step * ($i + .5), 1) }}" cy="{{ round($top + $plotH - $plotH * $v / $max, 1) }}" r="2.5" fill="{{ $colors[$si % 3] }}"><title>{{ $r[$x] ?? '' }}: {{ $label($v) }}</title></circle>
            @endforeach
        @endforeach
    @else
        @php($bw = max(2, $step / (count($series) + 1)))
        @foreach($rows as $i => $r)
            @if(! empty($links[$i]))<a href="{{ $links[$i] }}">@endif
            @foreach($series as $si => $s)
                @php($v = (float) ($r[$s['key']] ?? 0))
                @php($bh = $plotH * $v / $max)
                <rect x="{{ round($left + $step * $i + $bw * ($si + .5), 1) }}" y="{{ round($top + $plotH - $bh, 1) }}" width="{{ round($bw * .9, 1) }}" height="{{ round(max(0, $bh), 1) }}" fill="{{ $colors[$si % 3] }}"><title>{{ $r[$x] ?? '' }}: {{ $label($v) }}</title></rect>
            @endforeach
            @if(! empty($links[$i]))</a>@endif
        @endforeach
    @endif
    @foreach($rows as $i => $r)
        @if($i % $every === 0)<text x="{{ round($left + $step * ($i + .5), 1) }}" y="{{ $h - 8 }}" text-anchor="middle" font-size="10" fill="currentColor" fill-opacity=".7">{{ \Illuminate\Support\Str::limit((string) ($r[$x] ?? ''), 10, '') }}</text>@endif
    @endforeach
</svg>
@if(count($series) > 1)<div class="small text-muted">@foreach($series as $si => $s)<span class="me-3"><span style="display:inline-block;width:10px;height:10px;background:{{ $colors[$si % 3] }}"></span> {{ __($s['label']) }}</span>@endforeach</div>@endif
@endif
