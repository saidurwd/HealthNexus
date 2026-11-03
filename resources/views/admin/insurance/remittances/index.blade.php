@extends('layouts.adminlte')

@section('page_title', __('Payments from payers'))

@section('page_content')
    @include('admin.insurance._flash')
    @can('insurance.remittance.record')
    <form method="POST" action="{{ route('admin.insurance.remittances.store') }}" class="card mb-3">@csrf
        <div class="card-header"><h3 class="card-title">{{ __('Record a payment received') }}</h3></div>
        <div class="card-body d-flex flex-wrap gap-2"><select name="payer" class="form-select w-auto" required><option value="">{{ __('Payer') }}</option>@foreach($payers as $p)<option value="{{ $p->public_id }}">{{ $p->name }}</option>@endforeach</select>
            <input type="number" step="0.01" name="amount" class="form-control w-auto" placeholder="{{ __('Amount') }}" required><input type="date" name="received_on" class="form-control w-auto" value="{{ now()->toDateString() }}" required><input name="payer_reference" class="form-control w-auto" placeholder="{{ __('Cheque / transfer reference') }}" maxlength="80"><button class="btn btn-danger">{{ __('Record') }}</button></div></form>
    @endcan
    @if($writeoffs->isNotEmpty())<div class="card border-warning"><div class="card-header"><h3 class="card-title">{{ __('Write-offs waiting for approval') }}</h3></div><ul class="list-group list-group-flush small">
        @foreach($writeoffs as $w)<li class="list-group-item d-flex justify-content-between align-items-center"><span>{{ $claimNumbers[$w->claim_id] ?? '' }} — {{ $w->amount }} — {{ $w->reason }}</span>@can('insurance.writeoff.approve')<form method="POST" action="{{ route('admin.insurance.writeoffs.decide', $w) }}" class="d-flex gap-1">@csrf<button name="decision" value="approve" class="btn btn-sm btn-success">{{ __('Approve') }}</button><button name="decision" value="reject" class="btn btn-sm btn-outline-secondary">{{ __('Reject') }}</button></form>@endcan</li>@endforeach</ul></div>@endif
    <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0">
        <thead><tr><th>{{ __('Number') }}</th><th>{{ __('Payer') }}</th><th>{{ __('Received') }}</th><th class="text-end">{{ __('Amount') }}</th><th class="text-end">{{ __('Matched') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
        @forelse($remittances as $r)<tr><td><a href="{{ route('admin.insurance.remittances.show', $r) }}">{{ $r->remittance_number }}</a></td><td>{{ $r->payer->name }}</td><td>{{ $r->received_on->format('d M Y') }}</td><td class="text-end">{{ $r->amount }}</td><td class="text-end">{{ $r->allocated_amount }}</td><td><x-ui.status-badge :status="$r->status" /></td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('No payments recorded.') }}</td></tr>@endforelse
        </tbody></table></div><div class="card-footer">{{ $remittances->links() }}</div></div>
@stop
