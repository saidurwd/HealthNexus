@extends('layouts.adminlte')

@section('page_title', $p->number)

@section('page_content')
    @include('admin.finance._flash')
    @php($m = fn ($v) => number_format((float) $v, 2))
    <div class="card mb-3"><div class="card-body row g-2 small">
        <div class="col-md-3"><div class="text-muted">{{ __('Supplier') }}</div>{{ $supplier?->name }}</div><div class="col-md-3"><div class="text-muted">{{ __('Date') }}</div>{{ $p->payment_date->format('d M Y') }}</div>
        <div class="col-md-3"><div class="text-muted">{{ __('Paid from') }}</div>{{ $bank?->name }}</div><div class="col-md-3"><div class="text-muted">{{ __('Status') }}</div><x-ui.status-badge :status="$p->status" /></div>
        <div class="col-md-3"><div class="text-muted">{{ __('Invoices') }}</div>{{ $m($p->gross_amount) }}</div><div class="col-md-3"><div class="text-muted">{{ __('AIT/TDS withheld') }}</div>{{ $m($p->withheld_ait) }}</div>
        <div class="col-md-3"><div class="text-muted">{{ __('VAT withheld') }}</div>{{ $m($p->withheld_vat) }}</div><div class="col-md-3"><div class="text-muted">{{ __('Money paid out') }}</div><strong>{{ $m($p->net_amount) }}</strong></div>
        @if($p->reference)<div class="col-md-3"><div class="text-muted">{{ __('Reference') }}</div>{{ $p->reference }}</div>@endif @if($p->note)<div class="col-12">{{ $p->note }}</div>@endif</div></div>
    <div class="card mb-3"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Invoice') }}</th><th class="text-end">{{ __('Paid') }}</th></tr></thead><tbody>@foreach($lines as $l)<tr><td>{{ $payables[$l->payable_id]->invoice_number ?? '' }}</td><td class="text-end">{{ $m($l->amount) }}</td></tr>@endforeach</tbody></table></div>
    <div class="d-flex gap-2 flex-wrap">
        @if($p->status === 'draft')@can('finance.payable.approve')<form method="POST" action="{{ route('admin.finance.payments.act', $p->public_id) }}">@csrf<input type="hidden" name="action" value="approve"><button class="btn btn-success">{{ __('Approve') }}</button></form>@endcan @endif
        @if($p->status === 'approved')@can('finance.payable.pay')<form method="POST" action="{{ route('admin.finance.payments.act', $p->public_id) }}">@csrf<input type="hidden" name="action" value="pay"><button class="btn btn-primary">{{ __('Pay now') }}</button></form>@endcan @endif
        @if(in_array($p->status, ['draft', 'approved'], true))@can('finance.payable.manage')<form method="POST" action="{{ route('admin.finance.payments.act', $p->public_id) }}" class="d-flex gap-1">@csrf<input type="hidden" name="action" value="cancel"><input name="note" class="form-control" placeholder="{{ __('Why?') }}" required aria-label="{{ __('Reason') }}"><button class="btn btn-outline-danger">{{ __('Cancel') }}</button></form>@endcan @endif
        @if($p->status === 'paid')@can('finance.journal.reverse')<form method="POST" action="{{ route('admin.finance.payments.act', $p->public_id) }}" class="d-flex gap-1">@csrf<input type="hidden" name="action" value="reverse"><input name="note" class="form-control" placeholder="{{ __('Why is it reversed?') }}" required aria-label="{{ __('Reason') }}"><button class="btn btn-outline-danger">{{ __('Reverse') }}</button></form>@endcan @endif
    </div>
@stop
