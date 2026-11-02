@props(['tiles'])
{{--
    A row of headline figures from a list of [label, value, warn, url, unit?]. A figure that needs attention (warn) is drawn as a warning; a figure that cannot be worked out (null) is a dash; a figure with a url opens its list.
    For pages whose figures are standing counts rather than period figures (no previous period to compare with).
--}}
<div class="row row-cols-2 row-cols-md-4 row-cols-xl-5 g-2 mb-3">
    @foreach($tiles as $t)
        <x-ui.kpi-tile :label="$t[0]" :value="$t[1]" :unit="$t[4] ?? (is_int($t[1]) ? 'count' : null)" :variant="($t[2] ?? false) ? 'danger' : 'primary'" :href="$t[3] ?? null" good-direction="none" />
    @endforeach
</div>
