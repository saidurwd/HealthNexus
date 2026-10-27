@extends('layouts.adminlte')

@section('page_title', __('Claim').' '.$claim->claim_number)

@section('page_content')
    <x-patient-banner :patient="$claim->patient" />
    @include('admin.insurance._flash')
    @if($claim->scrub_errors)
        <div class="alert alert-warning"><strong>{{ __('Fix these before the claim can go:') }}</strong><ul class="mb-0">@foreach($claim->scrub_errors as $e)<li>{{ __($e['message']) }} — <em>{{ __($e['hint']) }}</em></li>@endforeach</ul></div>
    @endif
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card"><div class="card-body"><dl class="row mb-0">
                <dt class="col-5">{{ __('Status') }}</dt><dd class="col-7"><x-ui.status-badge :status="$claim->status" /> @if($claim->isLocked())<small class="text-muted">🔒 {{ __('locked') }}</small>@endif</dd>
                <dt class="col-5">{{ __('Payer') }}</dt><dd class="col-7">{{ $claim->payer->name }}</dd>
                <dt class="col-5">{{ __('Visit date') }}</dt><dd class="col-7">{{ $claim->service_date->format('d M Y') }} · {{ __(\Illuminate\Support\Str::headline($claim->claim_type)) }}</dd>
                <dt class="col-5">{{ __('Total bill') }}</dt><dd class="col-7">{{ $claim->gross_amount }}</dd>
                <dt class="col-5">{{ __('Payer pays') }}</dt><dd class="col-7"><strong>{{ $claim->payer_share }}</strong> <small class="text-muted">({{ __('paid') }} {{ $claim->paid_amount }}, {{ __('left') }} {{ $claim->outstanding() }})</small></dd>
                <dt class="col-5">{{ __('Patient pays') }}</dt><dd class="col-7">{{ $claim->patient_share }} <small class="text-muted">({{ __('deductible') }} {{ $claim->deductible_amount }}, {{ __('co-pay') }} {{ $claim->copay_amount }}, {{ __('not covered') }} {{ $claim->non_covered_amount }})</small></dd>
                <dt class="col-5">{{ __('Filing deadline') }}</dt><dd class="col-7">{{ $claim->filing_deadline?->format('d M Y') ?? '—' }}</dd>
                <dt class="col-5">{{ __('Payer reference') }}</dt><dd class="col-7">{{ $claim->payer_reference ?? '—' }}</dd>
                <dt class="col-5">{{ __('Diagnoses') }}</dt><dd class="col-7">@forelse($claim->diagnoses ?? [] as $d){{ $d['code'] ?? '—' }} {{ $d['description'] ?? '' }}<br>@empty — @endforelse</dd>
                @if($versions->count() > 1)<dt class="col-5">{{ __('Versions') }}</dt><dd class="col-7">@foreach($versions as $v)<a href="{{ route('admin.insurance.claims.show', $v->public_id) }}">V{{ $v->version }}</a> @endforeach</dd>@endif
            </dl></div>
            <div class="card-footer d-flex flex-wrap gap-2">
                @can('insurance.claim.edit')@if($claim->status === 'draft')<form method="POST" action="{{ route('admin.insurance.claims.ready', $claim) }}">@csrf<button class="btn btn-success">{{ __('Check and make ready') }}</button></form><form method="POST" action="{{ route('admin.insurance.claims.rebuild', $claim) }}">@csrf<button class="btn btn-outline-secondary">{{ __('Rebuild from invoice') }}</button></form>@endif @endcan
                @can('insurance.claim.create')@if(in_array($claim->status, ['rejected', 'denied', 'queried']))<form method="POST" action="{{ route('admin.insurance.claims.version', $claim) }}">@csrf<button class="btn btn-outline-primary">{{ __('Make a new version') }}</button></form>@endif
                    @if(in_array($claim->status, ['partially_paid', 'paid', 'denied', 'written_off']))<form method="POST" action="{{ route('admin.insurance.claims.secondary', $claim) }}">@csrf<button class="btn btn-outline-primary">{{ __('Bill second policy') }}</button></form>@endif @endcan
                @can('insurance.claim.cancel')@if(in_array($claim->status, ['draft', 'ready', 'queried']))<form method="POST" action="{{ route('admin.insurance.claims.cancel', $claim) }}" class="d-flex gap-1">@csrf<input name="reason" class="form-control form-control-sm" placeholder="{{ __('Why?') }}"><button class="btn btn-sm btn-outline-danger">{{ __('Cancel claim') }}</button></form>@endif @endcan
            </div></div>
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Documents') }}</h3></div>
                <ul class="list-group list-group-flush small">@foreach($documents as $doc)<li class="list-group-item d-flex justify-content-between">{{ __(\Illuminate\Support\Str::headline($doc->document_type)) }} @if($doc->required)<span class="badge text-bg-secondary">{{ __('required') }}</span>@endif
                    @if($claim->isLocked())<span>{{ $doc->received ? '✔' : '—' }}</span>@else @can('insurance.claim.edit')<form method="POST" action="{{ route('admin.insurance.claims.document', [$claim, $doc]) }}">@csrf<button class="btn btn-sm {{ $doc->received ? 'btn-success' : 'btn-outline-secondary' }}">{{ $doc->received ? __('Attached') : __('Attach') }}</button></form>@endcan @endif</li>@endforeach</ul></div>
        </div>
        <div class="col-lg-7">
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Lines') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0">
                <thead><tr><th>{{ __('Item') }}</th><th class="text-end">{{ __('Bill') }}</th><th class="text-end">{{ __('Payer') }}</th><th class="text-end">{{ __('Patient') }}</th><th>{{ __('Why') }}</th></tr></thead><tbody>
                @foreach($lines as $l)<tr><td>{{ $l->description }}</td><td class="text-end">{{ $l->gross_amount }}</td><td class="text-end">{{ $l->payer_share }}</td><td class="text-end">{{ $l->patient_share }}</td><td class="small text-muted">{{ $l->note }}</td></tr>@endforeach
                </tbody></table></div></div>
            @can('insurance.query.manage')@if(in_array($claim->status, ['submitted', 'acknowledged', 'accepted', 'appealed']))
            <form method="POST" action="{{ route('admin.insurance.claims.reply', $claim) }}" class="card">@csrf<div class="card-header"><h3 class="card-title">{{ __('Payer replied') }}</h3></div>
                <div class="card-body d-flex flex-wrap gap-2"><select name="kind" class="form-select w-auto"><option value="query">{{ __('Query') }}</option><option value="rejection">{{ __('Rejection') }}</option><option value="denial">{{ __('Denial') }}</option></select>
                    <select name="reason_code" class="form-select w-auto"><option value="">{{ __('Reason code') }}</option>@foreach($reasons as $code => $r)<option value="{{ $code }}">{{ $code }} — {{ __($r[0]) }}</option>@endforeach</select><input name="text" class="form-control w-auto flex-grow-1" placeholder="{{ __('What the payer said') }}" required maxlength="500"><button class="btn btn-outline-warning">{{ __('Record') }}</button></div></form>
            @endif @endcan
            @if($queries->isNotEmpty())<div class="card"><div class="card-header"><h3 class="card-title">{{ __('Payer replies') }}</h3></div><ul class="list-group list-group-flush small">
                @foreach($queries as $q)<li class="list-group-item"><strong>{{ __(\Illuminate\Support\Str::headline($q->kind)) }}</strong> {{ $q->reason_code }} — {{ $q->reason_text }} <x-ui.status-badge :status="$q->status" />@if($q->response)<div class="text-muted">{{ $q->response }}</div>@endif
                    @if($q->kind === 'query' && $q->status === 'open')@can('insurance.query.manage')<form method="POST" action="{{ route('admin.insurance.queries.answer', $q) }}" class="d-flex gap-1 mt-1">@csrf<input name="response" class="form-control form-control-sm" placeholder="{{ __('Answer') }}" required><button class="btn btn-sm btn-outline-success">{{ __('Send answer') }}</button></form>@endcan @endif</li>@endforeach</ul></div>@endif
            @can('insurance.appeal.manage')@if($claim->status === 'denied')<form method="POST" action="{{ route('admin.insurance.claims.appeal', $claim) }}" class="card">@csrf<div class="card-body d-flex gap-2"><input name="grounds" class="form-control" placeholder="{{ __('Why the denial is wrong') }}" required minlength="10" maxlength="1000"><button class="btn btn-outline-primary">{{ __('File appeal') }}</button></div></form>@endif
                @foreach($appeals->where('status', 'filed') as $a)<form method="POST" action="{{ route('admin.insurance.appeals.decide', $a) }}" class="card">@csrf<div class="card-body d-flex gap-2"><span class="flex-grow-1">{{ __('Appeal filed') }}: {{ $a->grounds }}</span><select name="outcome" class="form-select w-auto"><option value="overturned">{{ __('Overturned') }}</option><option value="upheld">{{ __('Upheld') }}</option></select><input name="note" class="form-control w-auto" placeholder="{{ __('Note') }}"><button class="btn btn-outline-secondary">{{ __('Record decision') }}</button></div></form>@endforeach @endcan
            @can('insurance.writeoff.request')@if(in_array($claim->status, ['denied', 'partially_paid', 'appealed']))<form method="POST" action="{{ route('admin.insurance.claims.writeoff', $claim) }}" class="card">@csrf<div class="card-body d-flex gap-2"><input type="number" step="0.01" name="amount" class="form-control w-auto" value="{{ $claim->outstanding() }}" required><input name="reason" class="form-control" placeholder="{{ __('Why write it off?') }}" required maxlength="300"><button class="btn btn-outline-danger">{{ __('Ask to write off') }}</button></div></form>@endif @endcan
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Timeline') }}</h3></div><ul class="list-group list-group-flush small">
                @foreach($events->sortByDesc('occurred_at') as $e)<li class="list-group-item">{{ $e->occurred_at->format('d M H:i') }} — <strong>{{ __(\Illuminate\Support\Str::headline($e->event_type)) }}</strong>@if($e->to_status) → {{ __(\Illuminate\Support\Str::headline($e->to_status)) }}@endif @if($e->note)<span class="text-muted">· {{ $e->note }}</span>@endif @if($e->amount) <span class="float-end">{{ $e->amount }}</span>@endif</li>@endforeach</ul></div>
        </div>
    </div>
@stop
