@props(['rows', 'x', 'series', 'kind' => 'bar', 'unit' => 'count', 'height' => 210, 'links' => []])
{{-- Kept so the BI screens do not change: the chart itself now lives in x-ui.chart. --}}
<x-ui.chart :rows="$rows" :x="$x" :series="$series" :kind="$kind" :unit="$unit" :height="$height" :links="$links" />
