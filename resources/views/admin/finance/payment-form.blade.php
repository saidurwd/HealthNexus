@extends('layouts.adminlte')

@section('page_title', __('Pay suppliers'))

@section('page_content')
    @include('admin.finance._flash')
    @php($m = fn ($v) => number_format((float) $v, 2))
    <form method="GET" class="card card-body mb-3"><div class="row g-2 align-items-end"><div class="col-md-5"><label class="form-label" for="supplier">{{ __('Supplier') }}</label><select id="supplier" name="supplier" class="form-select" onchange="this.form.submit()"><option value="">{{ __('Choose') }}</option>@foreach($suppliers as $s)<option value="{{ $s->id }}" @selected($supplierId == $s->id)>{{ $s->name }}</option>@endforeach</select></div><noscript><div class="col-auto"><button class="btn btn-primary">{{ __('Show') }}</button></div></noscript></div></form>
    @if($supplierId)
    <form method="POST" action="{{ route('admin.finance.payments.store') }}" class="card"><div class="card-body">@csrf<input type="hidden" name="supplier_id" value="{{ $supplierId }}">
        <div class="table-responsive mb-3"><table class="table table-sm"><thead><tr><th>{{ __('Invoice') }}</th><th>{{ __('Due') }}</th><th class="text-end">{{ __('Still owed') }}</th><th style="width:10rem">{{ __('Pay now') }}</th></tr></thead><tbody>
            @forelse($open as $p)<tr><td>{{ $p->invoice_number }}</td><td>{{ $p->due_date?->format('d M Y') }}</td><td class="text-end">{{ $m($svc->outstanding($p)) }}</td><td><input name="pay[{{ $p->id }}]" inputmode="decimal" class="form-control form-control-sm text-end" aria-label="{{ __('Pay now') }}"></td></tr>@empty<tr><td colspan="4" class="text-muted">{{ __('This supplier has nothing open.') }}</td></tr>@endforelse</tbody></table></div>
        <div class="row g-2">
            <div class="col-md-2"><label class="form-label" for="payment_date">{{ __('Date') }}</label><input type="date" id="payment_date" name="payment_date" class="form-control" required value="{{ now()->setTimezone('Asia/Dhaka')->toDateString() }}"></div>
            <div class="col-md-2"><label class="form-label" for="method">{{ __('How') }}</label><select id="method" name="method" class="form-select">@foreach(['bank' => 'Bank transfer', 'cheque' => 'Cheque', 'cash' => 'Cash', 'wallet' => 'Mobile wallet'] as $k => $l)<option value="{{ $k }}">{{ __($l) }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label" for="bank_account_id">{{ __('Paid from') }}</label><select id="bank_account_id" name="bank_account_id" class="form-select">@foreach($banks as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label" for="withheld_ait">{{ __('AIT/TDS withheld') }}</label><input id="withheld_ait" name="withheld_ait" inputmode="decimal" class="form-control" value="0"></div>
            <div class="col-md-2"><label class="form-label" for="withheld_vat">{{ __('VAT withheld') }}</label><input id="withheld_vat" name="withheld_vat" inputmode="decimal" class="form-control" value="0"></div>
            <div class="col-md-4"><label class="form-label" for="reference">{{ __('Cheque or transfer number') }}</label><input id="reference" name="reference" class="form-control"></div>
            <div class="col-md-8"><label class="form-label" for="note">{{ __('Note') }}</label><input id="note" name="note" class="form-control"></div>
        </div><p class="text-muted small mt-2">{{ __('The tax amounts are entered by the accountant; this screen does not decide a rate.') }}</p>
    </div><div class="card-footer"><button class="btn btn-primary">{{ __('Prepare the payment') }}</button></div></form>@endif
@stop
