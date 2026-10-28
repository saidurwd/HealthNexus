@extends('layouts.adminlte')

@section('page_title', __('Patient portal'))

@section('page_content')
    @include('admin.portal._flash')
    @php($tiles = [
        [__('Patients with a login'), $d['accounts']['active'], false, route('admin.portal.accounts', ['status' => 'active'])],
        [__('Joined in the last 30 days'), $d['accounts']['new_30d'], false, null],
        [__('Signed in during the last 7 days'), $d['accounts']['logged_in_7d'], false, null],
        [__('Locked or suspended logins'), $d['accounts']['locked'], $d['accounts']['locked'] > 0, route('admin.portal.accounts', ['status' => 'locked'])],
        [__('Results waiting for a clinician'), $d['results']['waiting_review'], $d['results']['waiting_review'] > 0, route('admin.portal.releases')],
        [__('Critical results held back'), $d['results']['held_critical'], $d['results']['held_critical'] > 0, route('admin.portal.releases', ['status' => 'held_critical'])],
        [__('Results kept off the portal'), $d['results']['withheld'], false, route('admin.portal.releases', ['status' => 'withheld'])],
        [__('Refill requests waiting'), $d['refills_open'], $d['refills_open'] > 0, route('admin.portal.refills')],
        [__('Messages waiting for a reply'), $d['messages']['open'], false, route('admin.portal.threads')],
        [__('Urgent messages'), $d['messages']['urgent'], $d['messages']['urgent'] > 0, route('admin.portal.threads')],
        [__('Messages past their reply time'), $d['messages']['overdue'], $d['messages']['overdue'] > 0, route('admin.portal.threads')],
        [__('Requests open'), $d['requests_open'], false, route('admin.portal.requests')],
        [__('Relatives waiting for approval'), $d['proxies_waiting'], $d['proxies_waiting'] > 0, route('admin.portal.proxies')],
        [__('Online payments in progress'), $d['payments']['pending'], false, route('admin.portal.payments')],
        [__('Online payments to refund'), $d['payments']['to_refund'], $d['payments']['to_refund'] > 0, route('admin.portal.payments', ['status' => 'to_refund'])],
        [__('Online payments not yet matched to the provider file'), $d['payments']['unreconciled'], false, route('admin.portal.payments', ['status' => 'unreconciled'])],
    ])
    <div class="row g-3 mb-3">
        @foreach($tiles as [$label, $value, $warn, $url])
            <div class="col-6 col-md-4 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ $label }}</div>
                <div class="fs-4 fw-semibold {{ $warn ? 'text-danger' : '' }}">@if($url)<a href="{{ $url }}" class="{{ $warn ? 'text-danger' : '' }}">{{ $value }}</a>@else{{ $value }}@endif</div></div></div></div>
        @endforeach
    </div>
    <div class="row g-3">
        <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Sign-in events, last 7 days') }}</h3></div>
            <ul class="list-group list-group-flush small">@forelse($d['security_7d'] as $kind => $n)<li class="list-group-item d-flex justify-content-between"><span>{{ __(\Illuminate\Support\Str::headline($kind)) }}</span><strong>{{ $n }}</strong></li>@empty<li class="list-group-item text-muted">{{ __('Nothing yet.') }}</li>@endforelse</ul></div></div>
        <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Messages sent to patients, last 7 days') }}</h3></div>
            <ul class="list-group list-group-flush small">@forelse($d['outbox'] as $status => $n)<li class="list-group-item d-flex justify-content-between"><x-ui.status-badge :status="$status" /><strong>{{ $n }}</strong></li>@empty<li class="list-group-item text-muted">{{ __('Nothing yet.') }}</li>@endforelse</ul></div></div>
    </div>
@stop
