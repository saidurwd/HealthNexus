@extends('layouts.adminlte')

@section('page_title', __('Claims'))

@section('page_content')
    @include('admin.insurance._flash')
    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control w-auto" placeholder="{{ __('Claim number') }}">
        <select name="status" class="form-select w-auto"><option value="">{{ __('Any status') }}</option>@foreach($statuses as $s)<option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ __(\Illuminate\Support\Str::headline($s)) }}</option>@endforeach</select>
        <select name="payer" class="form-select w-auto"><option value="">{{ __('Any payer') }}</option>@foreach($payers as $p)<option value="{{ $p->public_id }}" @selected(($filters['payer'] ?? '') === $p->public_id)>{{ $p->name }}</option>@endforeach</select>
        <button class="btn btn-outline-secondary">{{ __('Filter') }}</button>
    </form>
    @can('insurance.claim.create')
    <form method="POST" action="{{ route('admin.insurance.claims.store') }}" class="card mb-3">@csrf<div class="card-body d-flex gap-2"><input name="invoice" class="form-control" placeholder="{{ __('Invoice number to make a claim from') }}" required><button class="btn btn-danger">{{ __('Make claim') }}</button></div></form>
    @endcan
    <form method="POST" action="{{ route('admin.insurance.claims.export') }}" id="batch">@csrf
        <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0">
            <thead><tr><th></th><th>{{ __('Claim') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Payer') }}</th><th>{{ __('Visit date') }}</th><th class="text-end">{{ __('Payer share') }}</th><th class="text-end">{{ __('Paid') }}</th><th>{{ __('Status') }}</th><th>{{ __('Deadline') }}</th></tr></thead><tbody>
            @forelse($claims as $c)
                <tr><td>@if($c->status === 'ready')<input type="checkbox" name="claims[]" value="{{ $c->public_id }}" data-payer="{{ $c->payer->public_id }}">@endif</td><td><a href="{{ route('admin.insurance.claims.show', $c) }}">{{ $c->claim_number }}</a></td><td>{{ $c->patient->full_name }}</td><td>{{ $c->payer->name }}</td><td>{{ $c->service_date->format('d M Y') }}</td>
                    <td class="text-end">{{ $c->payer_share }}</td><td class="text-end">{{ $c->paid_amount }}</td><td><x-ui.status-badge :status="$c->status" /></td><td class="{{ $c->filing_deadline && $c->filing_deadline->lt(today()) && in_array($c->status, ['draft', 'ready']) ? 'text-danger' : '' }}">{{ $c->filing_deadline?->format('d M Y') ?? '—' }}</td></tr>
            @empty<tr><td colspan="9" class="text-muted">{{ __('No claims.') }}</td></tr>@endforelse
            </tbody></table></div><div class="card-footer d-flex gap-2">{{ $claims->links() }}
            @can('insurance.claim.submit')<span class="ms-auto d-flex gap-2"><select name="payer" class="form-select w-auto" required><option value="">{{ __('Payer for the batch') }}</option>@foreach($payers as $p)<option value="{{ $p->public_id }}">{{ $p->name }}</option>@endforeach</select><button class="btn btn-outline-primary">{{ __('Make batch file from ticked claims') }}</button></span>@endcan</div></div>
    </form>
    @can('insurance.claim.submit')
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Recent batches') }}</h3></div>
        <ul class="list-group list-group-flush small">@forelse($submissions as $s)
            <li class="list-group-item d-flex justify-content-between align-items-center"><span>{{ $s->batch_number }} · {{ $s->claim_count }} {{ __('claims') }} · {{ $s->total_amount }}</span>
                <span class="d-flex gap-2"><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.insurance.submissions.download', $s) }}">{{ __('Download file') }}</a>
                @if(! $s->submitted_at)<form method="POST" action="{{ route('admin.insurance.submissions.submitted', $s) }}" class="d-flex gap-1">@csrf<input name="payer_reference" class="form-control form-control-sm" placeholder="{{ __('Payer reference') }}" required><button class="btn btn-sm btn-success">{{ __('Mark submitted') }}</button></form>@else<span class="text-muted">{{ __('Submitted') }} {{ $s->submitted_at->format('d M') }} · {{ $s->payer_reference }}</span>@endif</span></li>
        @empty<li class="list-group-item text-muted">{{ __('No batches yet.') }}</li>@endforelse</ul></div>
    @endcan
@stop
