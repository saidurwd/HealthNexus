@extends('layouts.adminlte')

@section('page_title', __('Payment').' '.$remittance->remittance_number)

@section('page_content')
    @include('admin.insurance._flash')
    <div class="card"><div class="card-body"><strong>{{ $remittance->payer->name }}</strong> — {{ $remittance->amount }} · {{ __('left to match') }}: <strong>{{ $remittance->unallocated() }}</strong> · <x-ui.status-badge :status="$remittance->status" /> @if($remittance->payer_reference)· {{ $remittance->payer_reference }}@endif</div></div>
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Match to claims') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>{{ __('Claim') }}</th><th>{{ __('Patient') }}</th><th class="text-end">{{ __('Payer owes') }}</th><th></th></tr></thead><tbody>
        @forelse($open as $c)<tr><td><a href="{{ route('admin.insurance.claims.show', $c) }}">{{ $c->claim_number }}</a></td><td>{{ $c->patient->full_name }}</td><td class="text-end">{{ $c->outstanding() }}</td>
            <td>@can('insurance.remittance.record')<form method="POST" action="{{ route('admin.insurance.remittances.allocate', $remittance) }}" class="d-flex gap-1">@csrf<input type="hidden" name="claim" value="{{ $c->public_id }}"><input type="number" step="0.01" name="paid" class="form-control form-control-sm" style="width:110px" value="0"><input type="number" step="0.01" name="adjustment" class="form-control form-control-sm" style="width:110px" placeholder="{{ __('Disallowed') }}"><input name="adjustment_reason" class="form-control form-control-sm" placeholder="{{ __('Why disallowed') }}"><button class="btn btn-sm btn-outline-primary">{{ __('Match') }}</button></form>@endcan</td></tr>
        @empty<tr><td colspan="4" class="text-muted">{{ __('No open claims for this payer.') }}</td></tr>@endforelse
        </tbody></table></div></div>
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Already matched') }}</h3></div><ul class="list-group list-group-flush small">@forelse($lines as $l)<li class="list-group-item">{{ $claimNumbers[$l->claim_id] ?? '' }} — {{ __('paid') }} {{ $l->paid_amount }} @if(bccomp((string) $l->adjustment_amount, '0', 2) > 0)· {{ __('disallowed') }} {{ $l->adjustment_amount }} ({{ $l->adjustment_reason }})@endif</li>@empty<li class="list-group-item text-muted">{{ __('Nothing matched yet.') }}</li>@endforelse</ul></div>
@stop
