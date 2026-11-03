@extends('layouts.adminlte')

@section('page_title', __('The gate'))

@section('page_content')
    @include('admin.facility._flash')
    <p class="small text-muted">{{ __('People inside now') }}: <strong>{{ $insideNow }}</strong></p>
    <form class="row g-2 mb-3" method="GET"><div class="col-md-4"><input name="number" class="form-control" placeholder="{{ __('Pass number') }}" value="{{ $number }}" autofocus></div><div class="col-md-2"><button class="btn btn-primary">{{ __('Look up') }}</button></div></form>
    @if($number !== '' && ! $pass)<div class="alert alert-warning">{{ __('No pass has that number.') }}</div>@endif
    @if($pass)<div class="card mb-3 {{ $why ? 'border-danger' : 'border-success' }}"><div class="card-body"><h4>{{ $pass->visitor_name }} <small class="text-muted">{{ $pass->number }} · {{ \Illuminate\Support\Str::headline($pass->kind) }}</small></h4>
        <p class="mb-1">{{ __('To see') }}: {{ $pass->patient?->enterprise_patient_no }} {{ $pass->patient?->first_name }}</p>
        @if($why)<div class="alert alert-danger py-1">{{ $why }}</div>@else<div class="alert alert-success py-1">{{ __('May enter.') }}</div>@endif
        <div class="d-flex gap-2"><form method="POST" action="{{ route('admin.facility.gate.enter') }}" class="d-flex gap-1">@csrf<input type="hidden" name="number" value="{{ $pass->number }}"><input name="gate" class="form-control form-control-sm" placeholder="{{ __('Gate') }}"><button class="btn btn-success" @disabled($inside)>{{ __('In') }}</button></form>
            <form method="POST" action="{{ route('admin.facility.gate.leave') }}">@csrf<input type="hidden" name="number" value="{{ $pass->number }}"><button class="btn btn-outline-secondary" @disabled(! $inside)>{{ __('Out') }}</button></form></div></div></div>@endif
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Latest at the gate') }}</h3></div><ul class="list-group list-group-flush small">@forelse($log as $l)<li class="list-group-item d-flex justify-content-between"><span>{{ $l->created_at->setTimezone('Asia/Dhaka')->format('d M H:i') }} · {{ $l->pass?->visitor_name }} · {{ strtoupper($l->direction) }} @if($l->outcome === 'refused')<span class="badge text-bg-danger">{{ __('Refused') }}</span> {{ $l->note }}@endif</span><span class="text-muted">{{ $l->gate }}</span></li>@empty<li class="list-group-item text-muted">{{ __('Nothing yet.') }}</li>@endforelse</ul></div>
@stop
