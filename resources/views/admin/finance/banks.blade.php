@extends('layouts.adminlte')

@section('page_title', __('Bank, cash and wallets'))

@section('page_content')
    @include('admin.finance._flash')
    @php($m = fn ($v) => number_format((float) $v, 2))
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Name') }}</th><th>{{ __('Kind') }}</th><th>{{ __('Bank') }}</th><th>{{ __('Last digits') }}</th><th>{{ __('Receives payments by') }}</th><th class="text-end">{{ __('Balance in the books') }}</th></tr></thead><tbody>
        @forelse($rows as $r)<tr><td>{{ $r['bank']->name }}</td><td>{{ __(\Illuminate\Support\Str::headline($r['bank']->kind)) }}</td><td>{{ $r['bank']->bank_name }}</td><td>{{ $r['bank']->number_last4 }}</td><td>{{ $r['bank']->payment_method_code }}</td><td class="text-end">{{ $m($r['balance']) }}</td></tr>@empty<tr><td colspan="6" class="text-muted">{{ __('No accounts yet.') }}</td></tr>@endforelse</tbody></table></div></div>
    @can('finance.bank.manage')<form method="POST" action="{{ route('admin.finance.banks.store') }}" class="card mt-3"><div class="card-header"><h3 class="card-title">{{ __('Add an account') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-4"><label class="form-label" for="name">{{ __('Name') }}</label><input id="name" name="name" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label" for="kind">{{ __('Kind') }}</label><select id="kind" name="kind" class="form-select">@foreach(['bank', 'cash', 'wallet', 'gateway'] as $k)<option value="{{ $k }}">{{ __(\Illuminate\Support\Str::headline($k)) }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="account_id">{{ __('Ledger account') }}</label><select id="account_id" name="account_id" class="form-select">@foreach($ledger as $a)<option value="{{ $a->id }}">{{ $a->code }} {{ $a->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="bank_name">{{ __('Bank') }}</label><input id="bank_name" name="bank_name" class="form-control"></div>
        <div class="col-md-2"><label class="form-label" for="number_last4">{{ __('Last four digits only') }}</label><input id="number_last4" name="number_last4" maxlength="4" class="form-control"></div>
        <div class="col-md-3"><label class="form-label" for="payment_method_code">{{ __('Billing payment method it receives (code)') }}</label><input id="payment_method_code" name="payment_method_code" class="form-control" placeholder="CASH, CARD, MFS..."></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary">{{ __('Add') }}</button></div></div></form>@endcan
@stop
