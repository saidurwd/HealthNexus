@extends('layouts.adminlte')

@section('page_title', __('Patient login'))

@section('page_content')
    @include('admin.portal._flash')
    <div class="card"><div class="card-body d-flex flex-wrap justify-content-between gap-3">
        <div><div class="fs-5">{{ $a->patient->first_name }} {{ $a->patient->last_name }} <span class="text-muted">{{ $a->patient->enterprise_patient_no }}</span></div>
            <div class="small text-muted">{{ __('Phone') }} {{ \App\Services\Portal\PhoneNumber::mask($a->phone) }} · {{ __('Language') }} {{ strtoupper($a->language) }} · {{ __('Terms accepted') }} {{ $a->terms_accepted_at?->toDateString() ?? '—' }}@if($a->isLocked()) · <span class="text-danger">{{ __('locked until') }} {{ $a->locked_until->format('H:i') }}</span>@endif</div></div>
        <div><x-ui.status-badge :status="$a->status" /></div>
    </div>
    @can('portal.account.manage')<div class="card-footer d-flex flex-wrap gap-2">
        <form method="POST" action="{{ route('admin.portal.accounts.status', $a->public_id) }}" class="d-flex gap-2">@csrf<input name="reason" class="form-control form-control-sm" placeholder="{{ __('Why') }}"><button name="status" value="active" class="btn btn-sm btn-outline-success">{{ __('Allow / unlock') }}</button><button name="status" value="suspended" class="btn btn-sm btn-outline-warning">{{ __('Suspend') }}</button><button name="status" value="closed" class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('Close this login for good?') }}')">{{ __('Close') }}</button></form>
        <form method="POST" action="{{ route('admin.portal.accounts.sign-out', $a->public_id) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ __('Sign out of every device') }}</button></form></div>@endcan</div>
    <div class="row g-3">
        <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Devices') }}</h3></div><ul class="list-group list-group-flush small">@forelse($devices as $d)<li class="list-group-item d-flex justify-content-between"><span>{{ $d->name }} · {{ $d->platform }} · {{ $d->last_seen_at?->diffForHumans() }}</span>@if($d->revoked_at)<span class="text-muted">{{ __('signed out') }}</span>@else<span class="text-success">{{ __('trusted') }}</span>@endif</li>@empty<li class="list-group-item text-muted">{{ __('None.') }}</li>@endforelse</ul></div>
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Relatives this person acts for') }}</h3></div><ul class="list-group list-group-flush small">@forelse($proxiesOut as $p)<li class="list-group-item">{{ $p->patient->first_name }} {{ $p->patient->last_name }} · {{ __(\Illuminate\Support\Str::headline($p->relationship)) }} · <x-ui.status-badge :status="$p->status" /> · {{ implode(', ', array_map(fn ($s) => __(\Illuminate\Support\Str::headline($s)), (array) $p->scopes)) }}</li>@empty<li class="list-group-item text-muted">{{ __('None.') }}</li>@endforelse</ul></div>
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Relatives who can see this person') }}</h3></div><ul class="list-group list-group-flush small">@forelse($proxiesIn as $p)<li class="list-group-item">{{ $p->account->patient->first_name }} {{ $p->account->patient->last_name }} · {{ __(\Illuminate\Support\Str::headline($p->relationship)) }} · <x-ui.status-badge :status="$p->status" /> {{ $p->valid_to?->toDateString() }}</li>@empty<li class="list-group-item text-muted">{{ __('None.') }}</li>@endforelse</ul></div></div>
        <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Sign-in history') }}</h3></div><ul class="list-group list-group-flush small">@forelse($events as $e)<li class="list-group-item d-flex justify-content-between"><span>{{ __(\Illuminate\Support\Str::headline($e->kind)) }} <span class="text-muted">{{ $e->ip }}</span></span><span class="text-muted">{{ $e->created_at->format('d M H:i') }}</span></li>@empty<li class="list-group-item text-muted">{{ __('Nothing yet.') }}</li>@endforelse</ul></div></div>
    </div>
@stop
