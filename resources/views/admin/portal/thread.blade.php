@extends('layouts.adminlte')

@section('page_title', $t->subject)

@section('page_content')
    @include('admin.portal._flash')
    <div class="alert alert-secondary small">{{ __('A message is not an order. Do not start treatment, tests or prescriptions from a message: ask the patient to book a visit or follow the usual order route.') }}</div>
    <div class="card"><div class="card-header d-flex justify-content-between"><span>{{ $t->patient->first_name }} {{ $t->patient->last_name }} <span class="text-muted">{{ $t->patient->enterprise_patient_no }}</span> · {{ __(\Illuminate\Support\Str::headline($t->category)) }}</span><span>@if($t->urgent)<span class="badge text-bg-danger">{{ __('Urgent') }}</span> @endif<x-ui.status-badge :status="$t->status" /></span></div>
        <div class="card-body">@foreach($messages as $m)<div class="mb-3 {{ $m->sender === 'staff' ? 'text-end' : '' }}"><div class="d-inline-block p-2 rounded text-start {{ $m->sender === 'patient' ? 'bg-light border' : ($m->sender === 'staff' ? 'bg-primary-subtle' : 'bg-warning-subtle') }}" style="max-width: 80%; white-space: pre-wrap;">{{ $m->body }}</div><div class="small text-muted">{{ $m->sender === 'staff' ? ($m->user?->name ?? __('Staff')) : ($m->sender === 'patient' ? __('Patient') : __('System')) }} · {{ $m->created_at?->format('d M H:i') }}</div></div>@endforeach</div>
        @can('portal.message.reply')@if($t->status !== 'closed')<div class="card-footer"><form method="POST" action="{{ route('admin.portal.threads.reply', $t->public_id) }}">@csrf<textarea name="body" class="form-control mb-2" rows="3" required maxlength="4000" placeholder="{{ __('Your reply') }}"></textarea><button class="btn btn-primary">{{ __('Send reply') }}</button></form>
            <div class="d-flex gap-2 mt-2"><form method="POST" action="{{ route('admin.portal.threads.assign', $t->public_id) }}" class="d-flex gap-2">@csrf<select name="assigned_to" class="form-select form-select-sm w-auto"><option value="">{{ __('Nobody') }}</option>@foreach($staff as $u)<option value="{{ $u->id }}" @selected($t->assigned_to === $u->id)>{{ $u->name }}</option>@endforeach</select><button class="btn btn-sm btn-outline-secondary">{{ __('Assign') }}</button></form>
                <form method="POST" action="{{ route('admin.portal.threads.close', $t->public_id) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ __('Close conversation') }}</button></form></div></div>@endif @endcan</div>
@stop
