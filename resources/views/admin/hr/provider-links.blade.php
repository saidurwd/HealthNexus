@extends('layouts.adminlte')

@section('page_title', __('Doctors and employees: who is who'))

@section('page_content')
    @include('admin.hr._flash')
    <p class="text-muted small">{{ __('A provider is a role a person plays in clinics; an employee is the person employed. Nothing is linked until you confirm it here. Where one provider could be several people, or one person several providers, it is marked so you look closely.') }}</p>
    <form method="POST" action="{{ route('admin.hr.provider-links.propose') }}" class="mb-3">@csrf<button class="btn btn-outline-primary">{{ __('Look for matches') }}</button></form>
    @forelse($groups as $g)
        <div class="card"><div class="card-header d-flex justify-content-between"><h3 class="card-title">{{ $g['provider']->name }} <small class="text-muted">{{ $g['provider']->license_number }}</small></h3>@if($g['ambiguous'])<span class="badge text-bg-warning">{{ __('Check carefully') }}</span>@endif</div>
            <ul class="list-group list-group-flush small">@foreach($g['candidates'] as $c)<li class="list-group-item d-flex justify-content-between align-items-center gap-2"><span><a href="{{ route('admin.hr.employees.show', $c->employee) }}">{{ $c->employee->fullName() }}</a> {{ $c->employee->employee_no }} · {{ __('match') }} {{ $c->score }}% <span class="text-muted">({{ $c->reasons }})</span></span>
                <form method="POST" action="{{ route('admin.hr.provider-links.decide', $c) }}" class="d-flex gap-1">@csrf<button name="decision" value="accept" class="btn btn-sm btn-outline-success">{{ __('Same person') }}</button><button name="decision" value="reject" class="btn btn-sm btn-outline-secondary">{{ __('Not the same') }}</button></form></li>@endforeach</ul></div>
    @empty<div class="alert alert-info">{{ __('Nothing to confirm.') }}</div>@endforelse
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Recently confirmed') }}</h3></div><ul class="list-group list-group-flush small">@forelse($accepted as $a)<li class="list-group-item">{{ $a->provider->name }} = {{ $a->employee->fullName() }}</li>@empty<li class="list-group-item text-muted">{{ __('None yet.') }}</li>@endforelse</ul></div>
    @can('hr.settings.manage')<form method="POST" action="{{ route('admin.hr.backfill') }}" class="mt-3">@csrf<button class="btn btn-sm btn-outline-secondary" onclick="return confirm('{{ __('Make an employee record for every login that has none?') }}')">{{ __('Make employee records from existing logins') }}</button></form>@endcan
@stop
