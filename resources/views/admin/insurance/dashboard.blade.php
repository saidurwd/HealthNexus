@extends('layouts.adminlte')

@section('page_title', __('Insurance and corporate claims'))

@section('page_content')
    @include('admin.insurance._flash')
    @php $L = fn ($route, $perm, $q = []) => \App\Support\Dashboard\Link::to($route, $perm, $q); $N = fn ($v) => \App\Support\Dashboard\Numbers::number($v); $c = $p['current']; $b = $p['previous']; @endphp
    <x-ui.dashboard :question="__('Are the claims being paid, and which are stuck or about to lapse?')" :audience="__('For the insurance desk and the finance manager')" :period="$period" :periods="['7d', '30d', '90d', 'month', 'last_month', 'custom']" :as-of="$asOf">
        <div class="row row-cols-2 row-cols-md-4 row-cols-xl-5 g-2 mb-3">
            <x-ui.kpi-tile :label="__('Claims sent')" :value="$c['sent']" :previous="$b['sent']" unit="count" icon="bi-send" variant="primary" :href="$L('admin.insurance.claims.index', 'insurance.claim.view')" good-direction="none" :note="__('worth :v', ['v' => \App\Support\Dashboard\Numbers::value($c['sent_value'], 'taka')])" />
            <x-ui.kpi-tile :label="__('Paid by payers')" :value="$c['paid']" :previous="$b['paid']" unit="taka" icon="bi-cash-coin" variant="success" :href="$L('admin.insurance.remittances.index', 'insurance.remittance.view')" good-direction="up" />
            <x-ui.kpi-tile :label="__('Payers owe us')" :value="(float) $d['outstanding']" unit="taka" icon="bi-hourglass-split" variant="warning" good-direction="down" />
            <x-ui.kpi-tile :label="__('Denied or rejected')" :value="$c['denial_rate']" :previous="$b['denial_rate']" unit="percent" icon="bi-x-octagon" variant="danger" good-direction="down" :target="app(\App\Services\Dashboard\Targets::class)->get('claim_denial_rate')" :note="$c['decided'] > 0 ? __('of :n decided claims', ['n' => $N($c['decided'])]) : __('No claim was decided in this period.')" />
            <x-ui.kpi-tile :label="__('Typical days to payment')" :value="$c['days_to_payment']['median'] ?? null" :previous="$b['days_to_payment']['median'] ?? null" unit="days" icon="bi-stopwatch" variant="info" good-direction="down" :note="$c['days_to_payment'] ? __('9 in 10 within :m days · :n claims', ['m' => $N($c['days_to_payment']['p90']), 'n' => $N($c['days_to_payment']['n'])]) : __('No claim was paid in this period.')" />
            <x-ui.kpi-tile :label="__('Waiting for pre-authorization')" :value="$d['pending_preauths']" unit="count" icon="bi-shield-check" variant="warning" :href="$L('admin.insurance.preauths', 'insurance.preauth.view')" good-direction="down" :note="__(':n end this week', ['n' => $N($d['expiring_preauths'])])" />
            <x-ui.kpi-tile :label="__('Filing deadline within 14 days')" :value="$d['deadlines_soon']" unit="count" icon="bi-calendar-event" variant="danger" :href="$L('admin.insurance.claims.index', 'insurance.claim.view')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Open payer queries')" :value="$d['open_queries']" unit="count" icon="bi-question-circle" variant="warning" good-direction="down" :note="__(':n write-offs waiting for approval', ['n' => $N($d['pending_writeoffs'])])" />
        </div>
        <div class="row g-3">
            <div class="col-lg-7"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Who owes what, and for how long') }}</h3></div>
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead><tr><th>{{ __('Payer') }}</th>@foreach(array_keys($aging->first()['buckets'] ?? ['0–30' => 0, '31–60' => 0, '61–90' => 0, '91+' => 0]) as $bk)<th class="text-end">{{ $bk }} {{ __('days') }}</th>@endforeach<th class="text-end">{{ __('Total') }}</th></tr></thead>
                    <tbody>@forelse($aging as $row)<tr><td>{{ $row['payer'] }} <small class="text-muted">({{ $row['claims'] }})</small></td>@foreach($row['buckets'] as $v)<td class="text-end">{{ $v }}</td>@endforeach<td class="text-end fw-semibold">{{ $row['total'] }}</td></tr>
                    @empty<tr><td colspan="6" class="text-muted">{{ __('Nothing is owed by any payer.') }}</td></tr>@endforelse</tbody></table></div></div></div>
            <div class="col-lg-5"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Claims by status') }}</h3></div>
                <ul class="list-group list-group-flush small">@forelse($d['by_status'] as $status => $row)<li class="list-group-item d-flex justify-content-between"><a href="{{ route('admin.insurance.claims.index', ['status' => $status]) }}"><x-ui.status-badge :status="$status" /></a><span>{{ $row['count'] }} · {{ $row['amount'] }}</span></li>@empty<li class="list-group-item text-muted">{{ __('No claims yet.') }}</li>@endforelse</ul></div>
                <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Why claims fail') }}</h3></div>
                <ul class="list-group list-group-flush small">@forelse($worklist as $w)<li class="list-group-item"><strong>{{ $w['code'] }}</strong> {{ __($w['label']) }} <span class="float-end">{{ $w['count'] }} · {{ $w['amount'] }}</span></li>@empty<li class="list-group-item text-muted">{{ __('No denials or rejections.') }}</li>@endforelse</ul></div></div>
        </div>
    </x-ui.dashboard>
@stop
