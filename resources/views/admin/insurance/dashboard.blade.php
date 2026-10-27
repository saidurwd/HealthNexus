@extends('layouts.adminlte')

@section('page_title', __('Insurance and corporate claims'))

@section('page_content')
    @include('admin.insurance._flash')
    <div class="row g-3 mb-3">
        @foreach([[__('Submitted this month'), $d['submitted_this_month']['count'].' · '.$d['submitted_this_month']['amount'], false], [__('Payers owe us'), $d['outstanding'], false], [__('Denied or rejected %'), $d['denial_rate_percent'].' %', $d['denial_rate_percent'] > 10],
                  [__('Average days to payment'), $d['avg_days_to_payment'] ?? '—', false], [__('Waiting for pre-authorization'), $d['pending_preauths'], $d['pending_preauths'] > 0], [__('Authorizations ending this week'), $d['expiring_preauths'], $d['expiring_preauths'] > 0],
                  [__('Filing deadline within 14 days'), $d['deadlines_soon'], $d['deadlines_soon'] > 0], [__('Open payer queries'), $d['open_queries'], $d['open_queries'] > 0], [__('Write-offs waiting for approval'), $d['pending_writeoffs'], $d['pending_writeoffs'] > 0]] as [$label, $value, $warn])
            <div class="col-6 col-md-4 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-semibold {{ $warn ? 'text-danger' : '' }}">{{ $value }}</div></div></div></div>
        @endforeach
    </div>
    <div class="row g-3">
        <div class="col-lg-7"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Who owes what, and for how long') }}</h3></div>
            <div class="table-responsive"><table class="table table-sm mb-0">
                <thead><tr><th>{{ __('Payer') }}</th>@foreach(array_keys($aging->first()['buckets'] ?? ['0–30' => 0, '31–60' => 0, '61–90' => 0, '91+' => 0]) as $b)<th class="text-end">{{ $b }} {{ __('days') }}</th>@endforeach<th class="text-end">{{ __('Total') }}</th></tr></thead>
                <tbody>@forelse($aging as $row)<tr><td>{{ $row['payer'] }} <small class="text-muted">({{ $row['claims'] }})</small></td>@foreach($row['buckets'] as $v)<td class="text-end">{{ $v }}</td>@endforeach<td class="text-end fw-semibold">{{ $row['total'] }}</td></tr>
                @empty<tr><td colspan="6" class="text-muted">{{ __('Nothing is owed by any payer.') }}</td></tr>@endforelse</tbody></table></div></div></div>
        <div class="col-lg-5"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Claims by status') }}</h3></div>
            <ul class="list-group list-group-flush small">@forelse($d['by_status'] as $status => $row)<li class="list-group-item d-flex justify-content-between"><a href="{{ route('admin.insurance.claims.index', ['status' => $status]) }}"><x-ui.status-badge :status="$status" /></a><span>{{ $row['count'] }} · {{ $row['amount'] }}</span></li>@empty<li class="list-group-item text-muted">{{ __('No claims yet.') }}</li>@endforelse</ul></div>
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Why claims fail') }}</h3></div>
            <ul class="list-group list-group-flush small">@forelse($worklist->take(5) as $w)<li class="list-group-item"><strong>{{ $w['code'] }}</strong> {{ __($w['label']) }} <span class="float-end">{{ $w['count'] }} · {{ $w['amount'] }}</span></li>@empty<li class="list-group-item text-muted">{{ __('No denials or rejections.') }}</li>@endforelse</ul></div></div>
    </div>
@stop
