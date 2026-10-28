@extends('layouts.adminlte')

@section('page_title', __('Patient relations'))

@section('page_content')
    @include('admin.crm._flash')
    @php($tiles = [
        [__('Contacts to follow up'), $d['interactions_open'], false, route('admin.crm.interactions')],
        [__('Follow-ups due today or late'), $d['follow_ups_due'], $d['follow_ups_due'] > 0, route('admin.crm.interactions')],
        [__('Complaints open'), $d['complaints']['open'], false, route('admin.crm.complaints')],
        [__('Complaints past their deadline'), $d['complaints']['overdue'], $d['complaints']['overdue'] > 0, route('admin.crm.complaints')],
        [__('Complaints escalated'), $d['complaints']['escalated'], $d['complaints']['escalated'] > 0, route('admin.crm.complaints', ['status' => 'escalated'])],
        [__('Hours to answer a complaint (average)'), $d['complaints']['avg_hours_to_resolve'] ?? '—', false, null],
        [__('Satisfaction with complaint answers (1 to 5)'), $d['complaints']['satisfaction'] ?? '—', false, null],
        [__('Visit rating, last 90 days (1 to 5)'), $d['feedback']['average'] ?? '—', false, route('admin.crm.feedback')],
        [__('Net promoter score'), $d['feedback']['nps'] ?? '—', false, null],
        [__('Low ratings not yet looked at'), $d['feedback_unreviewed'], $d['feedback_unreviewed'] > 0, route('admin.crm.feedback', ['low' => 1, 'status' => 'new'])],
        [__('Patients seen in 90 days who use the portal'), $d['portal_adoption']['percent'].'%', false, null],
    ])
    <div class="row g-3 mb-3">
        @foreach($tiles as [$label, $value, $warn, $url])
            <div class="col-6 col-md-4 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ $label }}</div>
                <div class="fs-4 fw-semibold {{ $warn ? 'text-danger' : '' }}">@if($url)<a href="{{ $url }}" class="{{ $warn ? 'text-danger' : '' }}">{{ $value }}</a>@else{{ $value }}@endif</div></div></div></div>
        @endforeach
    </div>
    <div class="row g-3">
        <div class="col-lg-4"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Complaints by kind') }}</h3></div><ul class="list-group list-group-flush small">@forelse($d['complaints']['by_category'] as $k => $n)<li class="list-group-item d-flex justify-content-between"><span>{{ __(\Illuminate\Support\Str::headline($k)) }}</span><strong>{{ $n }}</strong></li>@empty<li class="list-group-item text-muted">{{ __('None yet.') }}</li>@endforelse</ul></div></div>
        <div class="col-lg-4"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('How people contact us, last 30 days') }}</h3></div><ul class="list-group list-group-flush small">@forelse($d['channels_30d'] as $k => $n)<li class="list-group-item d-flex justify-content-between"><span>{{ __(\Illuminate\Support\Str::headline($k)) }}</span><strong>{{ $n }}</strong></li>@empty<li class="list-group-item text-muted">{{ __('None yet.') }}</li>@endforelse</ul></div></div>
        <div class="col-lg-4"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Campaigns') }}</h3></div><ul class="list-group list-group-flush small">@forelse($d['campaigns'] as $k => $n)<li class="list-group-item d-flex justify-content-between"><x-ui.status-badge :status="$k" /><strong>{{ $n }}</strong></li>@empty<li class="list-group-item text-muted">{{ __('None yet.') }}</li>@endforelse</ul></div></div>
    </div>
@stop
