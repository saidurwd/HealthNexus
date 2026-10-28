@extends('layouts.adminlte')

@section('page_title', __('My team'))

@section('page_content')
    @include('admin.hr._flash')
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Today') }}</h3></div><ul class="list-group list-group-flush small">@foreach($members as $m)@php($t = $today->firstWhere('employee_id', $m->id))<li class="list-group-item d-flex justify-content-between"><span>{{ $m->fullName() }}</span>@if($t)<x-ui.status-badge :status="$t->status" />@else<span class="text-muted">—</span>@endif</li>@endforeach</ul></div>
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Leave waiting for you') }}</h3></div>
        <ul class="list-group list-group-flush small">@forelse($leave as $l)<li class="list-group-item d-flex justify-content-between align-items-center gap-2"><span>{{ $l->employee->fullName() }} · {{ $l->type->name }} · {{ $l->from_date->toDateString() }} → {{ $l->to_date->toDateString() }} ({{ $l->days + 0 }})</span>
            @if($canLeave && $l->status === 'submitted')<form method="POST" action="{{ route('admin.hr.leave.decide', $l) }}" class="d-flex gap-1">@csrf<input type="hidden" name="as" value="manager"><button name="decision" value="approve" class="btn btn-sm btn-outline-success">{{ __('Approve') }}</button><button name="decision" value="reject" class="btn btn-sm btn-outline-danger">{{ __('Reject') }}</button></form>@endif</li>@empty<li class="list-group-item text-muted">{{ __('Nothing waiting.') }}</li>@endforelse</ul></div>
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Attendance corrections waiting for you') }}</h3></div>
        <ul class="list-group list-group-flush small">@forelse($corrections as $c)<li class="list-group-item d-flex justify-content-between align-items-center gap-2"><span>{{ $c->employee->fullName() }} · {{ $c->work_date->toDateString() }} · {{ $c->reason }}</span>
            @if($canCorrections)<form method="POST" action="{{ route('admin.hr.corrections.decide', $c) }}" class="d-flex gap-1">@csrf<button name="decision" value="approve" class="btn btn-sm btn-outline-success">{{ __('Approve') }}</button><button name="decision" value="reject" class="btn btn-sm btn-outline-danger">{{ __('Reject') }}</button></form>@endif</li>@empty<li class="list-group-item text-muted">{{ __('Nothing waiting.') }}</li>@endforelse</ul></div>
@stop
