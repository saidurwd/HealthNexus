@props(['question', 'audience' => null, 'period' => null, 'periods' => null, 'asOf' => null, 'refresh' => null])
{{--
    The frame of a dashboard (docs/ux/dashboard-standard.md): the one question it answers and for whom, the period (as a filter), when the figures were worked out ("as of") with a refresh button, and, for a live board, a
    note that it reloads itself. The figures go in the slot.
--}}
@php
    $tz = (string) config('ux.timezone', 'Asia/Dhaka');
    $kept = collect(request()->query())->except(['period', 'from', 'to', 'fresh', 'page'])->all();
    $options = $period ? \App\Support\Dashboard\DashboardPeriod::options($periods) : [];
    $names = ['today' => __('Today'), '7d' => __('Last 7 days'), '30d' => __('Last 30 days'), '90d' => __('Last 90 days'), 'month' => __('This month'), 'last_month' => __('Last month'), 'custom' => __('Choose dates')];
@endphp
<div data-dashboard>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <p class="lead mb-0" data-dashboard-question>{{ $question }}</p>
            @if($audience)<div class="small text-muted">{{ $audience }}</div>@endif
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @if($period)
                <form method="GET" action="{{ url()->current() }}" class="d-flex flex-wrap align-items-center gap-1" data-dashboard-filter>
                    @foreach($kept as $k => $v)@if(is_scalar($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif @endforeach
                    <label class="visually-hidden" for="dash-period">{{ __('Period') }}</label>
                    <select name="period" id="dash-period" class="form-select form-select-sm" style="width:auto" onchange="document.getElementById('dash-custom').classList.toggle('d-none', this.value !== 'custom'); if (this.value !== 'custom') this.form.submit();">
                        @foreach($options as $o)<option value="{{ $o }}" @selected($period->key === $o || ($period->key === 'custom' && $o === 'custom'))>{{ $names[$o] ?? $o }}</option>@endforeach
                    </select>
                    <span id="dash-custom" class="d-flex gap-1 {{ $period->key === 'custom' ? '' : 'd-none' }}">
                        <label class="visually-hidden" for="dash-from">{{ __('From') }}</label><input type="date" id="dash-from" name="from" value="{{ $period->fromDate() }}" class="form-control form-control-sm" style="width:auto">
                        <label class="visually-hidden" for="dash-to">{{ __('To') }}</label><input type="date" id="dash-to" name="to" value="{{ $period->toDate() }}" class="form-control form-control-sm" style="width:auto">
                        <button class="btn btn-sm btn-primary">{{ __('Show') }}</button>
                    </span>
                </form>
            @endif
            @if($asOf)
                <span class="small text-muted" data-dashboard-asof aria-live="polite">{{ __('As of') }} {{ \App\Support\Dashboard\Numbers::digits($asOf->setTimezone($tz)->format('d M, H:i')) }}</span>
                <a class="btn btn-sm btn-outline-secondary" href="{{ url()->current().'?'.http_build_query(array_merge($kept, $period ? $period->query() : [], ['fresh' => 1])) }}" title="{{ __('Work the figures out again now') }}"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i> {{ __('Refresh') }}</a>
            @endif
            @if($refresh)<span class="small text-muted" data-dashboard-refresh="{{ $refresh }}"><i class="bi bi-broadcast" aria-hidden="true"></i> {{ __('Reloads every :n seconds', ['n' => \App\Support\Dashboard\Numbers::digits((string) $refresh)]) }}</span>@endif
        </div>
    </div>
    @if($period && $period->key !== 'custom')<div class="small text-muted mb-2">{{ $period->label() }}@if($period->days() > 1): {{ $period->from->format('d M Y') }} – {{ $period->to->format('d M Y') }}@endif</div>@endif
    {{ $slot }}
</div>
@if($refresh)
@push('js')
<script>
(function () {
    var every = {{ (int) $refresh }} * 1000;
    function tick() {
        var busy = document.hidden || (document.activeElement && ['INPUT', 'SELECT', 'TEXTAREA'].indexOf(document.activeElement.tagName) !== -1);
        if (busy) { setTimeout(tick, 5000); return; }
        window.location.reload();
    }
    setTimeout(tick, every);
})();
</script>
@endpush
@endif
