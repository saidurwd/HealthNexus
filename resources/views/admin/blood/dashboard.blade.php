@extends('layouts.adminlte')

@section('page_title', __('Blood bank'))

@section('page_content')
    @php $L = fn ($route, $perm, $q = []) => \App\Support\Dashboard\Link::to($route, $perm, $q); $N = fn ($v) => \App\Support\Dashboard\Numbers::number($v); $c = $metrics['current']; $p = $metrics['previous']; @endphp
    @include('admin.blood._flash')
    <x-ui.dashboard :question="__('Is there blood for the next patient who needs it, and is any of it about to be wasted?')" :audience="__('For the blood bank medical officer')" :period="$period" :periods="['today', '7d', '30d', '90d', 'month', 'last_month', 'custom']" :as-of="$asOf">
        <div class="row row-cols-2 row-cols-md-4 row-cols-xl-5 g-2 mb-3">
            <x-ui.kpi-tile :label="__('Below minimum stock')" :value="$metrics['below_minimum']" unit="count" icon="bi-droplet-half" variant="danger" :href="$L('admin.blood.inventory.index', 'bloodbank.inventory.view')" good-direction="down" :note="__('kinds of blood product')" />
            <x-ui.kpi-tile :label="__('Expiring soon')" :value="$metrics['expiring']" unit="count" icon="bi-calendar-x" variant="warning" :href="$L('admin.blood.inventory.index', 'bloodbank.inventory.view')" good-direction="down" :note="__('within :n days', ['n' => $N($metrics['expiry_days'])])" />
            <x-ui.kpi-tile :label="__('Requests waiting')" :value="$metrics['requests_waiting']" unit="count" icon="bi-inbox" variant="primary" :href="$L('admin.blood.requests.index', 'bloodbank.request.view')" good-direction="down" :note="__(':n emergency', ['n' => $N($metrics['emergency_waiting'])])" />
            <x-ui.kpi-tile :label="__('Issued today')" :value="$metrics['issued_today']" unit="count" icon="bi-box-arrow-right" variant="success" good-direction="none" />
            <x-ui.kpi-tile :label="__('On the ward, not started')" :value="$metrics['on_ward_not_started']" unit="count" icon="bi-hospital" variant="warning" good-direction="down" />
            <x-ui.kpi-tile :label="__('Open incidents')" :value="$metrics['open_incidents']" unit="count" icon="bi-exclamation-octagon" variant="danger" :href="$L('admin.blood.incidents.index', 'bloodbank.incident.view')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Bags issued in the period')" :value="$c['issued']" :previous="$p['issued']" unit="count" icon="bi-droplet-fill" variant="primary" good-direction="none" />
            <x-ui.kpi-tile :label="__('Typical wait for blood')" :value="$c['wait']['median'] ?? null" :previous="$p['wait']['median'] ?? null" unit="minutes" icon="bi-stopwatch" variant="info" good-direction="down" :target="app(\App\Services\Dashboard\Targets::class)->get('blood_wait')" :note="$c['wait'] ? __('9 in 10 within :m min · :n requests', ['m' => $N($c['wait']['p90']), 'n' => $N($c['wait']['n'])]) : __('No blood was issued in this period.')" />
            <x-ui.kpi-tile :label="__('Blood wasted')" :value="$c['wastage_percent']" :previous="$p['wastage_percent']" unit="percent" icon="bi-trash" variant="secondary" good-direction="down" :note="__('thrown away or expired, of bags received')" />
        </div>
        <div class="row g-3 mb-3">
            <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Units on the shelf') }}</h3></div>
                <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Blood product') }}</th><th class="text-end">{{ __('Usable') }}</th><th class="text-end">{{ __('Minimum') }}</th></tr></thead><tbody>
                    @foreach($metrics['stock'] as $s)<tr class="{{ $s['minimum'] !== null && $s['available'] < $s['minimum'] ? 'table-danger' : '' }}"><td>{{ __($s['component']) }}</td><td class="text-end">{{ $N($s['available']) }}</td><td class="text-end">{{ $s['minimum'] !== null ? $N($s['minimum']) : '—' }}</td></tr>@endforeach
                </tbody></table></div></div></div>
            <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Bags issued per day') }} <span class="small text-muted">· {{ $period->label() }}</span></h3></div><div class="card-body">
                <x-ui.chart kind="bar" :rows="$metrics['per_day']" x="day" :series="[['key' => 'issued', 'label' => 'Issued']]" />
            </div></div></div>
        </div>
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Open requests') }}</h3></div>
                <ul class="list-group list-group-flush small">
                    @forelse($urgent as $r)
                        <li class="list-group-item d-flex justify-content-between"><span><a href="{{ route('admin.blood.requests.show', $r) }}">{{ $r->request_number }}</a> — {{ $r->patient?->full_name }} · {{ $r->units_requested }} × {{ __($labels[$r->component] ?? $r->component) }}</span>
                            <span><span class="badge text-bg-{{ in_array($r->priority, ['emergency', 'massive']) ? 'danger' : ($r->priority === 'urgent' ? 'warning' : 'secondary') }}">{{ __(\Illuminate\Support\Str::headline($r->priority)) }}</span> <x-ui.status-badge :status="$r->status" /></span></li>
                    @empty
                        <li class="list-group-item text-muted">{{ __('No open requests.') }}</li>
                    @endforelse
                </ul></div>
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Expiring soon') }}</h3></div>
                <ul class="list-group list-group-flush small">
                    @forelse($nearExpiry as $u)<li class="list-group-item"><a href="{{ route('admin.blood.units.show', $u) }}">{{ $u->unit_number }}</a> · {{ __($labels[$u->component] ?? $u->component) }} · {{ $u->abo }} {{ __(\Illuminate\Support\Str::headline($u->rh)) }} — {{ $u->expires_at->format('d M H:i') }}</li>
                    @empty<li class="list-group-item text-muted">{{ __('Nothing expires soon.') }}</li>@endforelse
                </ul></div>
        </div>
        <div class="col-lg-6">
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Storage') }}</h3></div>
                <ul class="list-group list-group-flush small">
                    @foreach($locations as $row)
                        <li class="list-group-item d-flex justify-content-between"><span>{{ $row['location']->name }} <span class="text-muted">({{ $row['location']->min_temp_c }} – {{ $row['location']->max_temp_c }} °C) · {{ $row['units'] }} {{ __('units') }}</span></span>
                            <span class="{{ $row['last']?->is_excursion ? 'text-danger fw-bold' : '' }}">{{ $row['last'] ? $row['last']->celsius.' °C · '.$row['last']->recorded_at->format('H:i') : '—' }}</span></li>
                    @endforeach
                </ul></div>
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Open incidents') }}</h3></div>
                <ul class="list-group list-group-flush small">
                    @forelse($incidents as $i)<li class="list-group-item"><strong>{{ __(\Illuminate\Support\Str::headline($i->incident_type)) }}</strong> — {{ $i->description }}</li>
                    @empty<li class="list-group-item text-muted">{{ __('No open incidents.') }}</li>@endforelse
                </ul></div>
        </div>
    </div>
    </x-ui.dashboard>
@stop
