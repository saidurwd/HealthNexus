@extends('layouts.adminlte')

@section('page_title', __('Banking the tills'))

@section('page_content')
    @include('admin.finance._flash')
    @php($m = fn ($v) => number_format((float) $v, 2))
    @can('finance.bank.manage')<div class="row g-3 mb-3">
        <div class="col-lg-6"><form method="POST" action="{{ route('admin.finance.deposits.session') }}" class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Bank a closed cashier session') }}</h3></div><div class="card-body row g-2">@csrf
            <div class="col-12"><label class="form-label" for="session_id">{{ __('Session') }}</label><select id="session_id" name="session_id" class="form-select">@forelse($sessions as $s)<option value="{{ $s->id }}">#{{ $s->id }} — {{ $s->closed_at?->setTimezone('Asia/Dhaka')->format('d M H:i') }} — {{ __('counted') }} {{ $m($s->actual_closing) }}</option>@empty<option value="">{{ __('Every closed session is banked.') }}</option>@endforelse</select></div>
            <div class="col-md-6"><label class="form-label" for="bank_account_id">{{ __('Into') }}</label><select id="bank_account_id" name="bank_account_id" class="form-select">@foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label" for="amount">{{ __('Amount (empty = what was counted less the float)') }}</label><input id="amount" name="amount" class="form-control"></div>
            <div class="col-md-3"><label class="form-label" for="reference">{{ __('Slip number') }}</label><input id="reference" name="reference" class="form-control"></div>
        </div><div class="card-footer"><button class="btn btn-primary">{{ __('Bank it') }}</button></div></form></div>
        <div class="col-lg-6"><form method="POST" action="{{ route('admin.finance.deposits.transfer') }}" class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Move money between accounts') }}</h3></div><div class="card-body row g-2">@csrf
            <div class="col-md-6"><label class="form-label" for="from_id">{{ __('From') }}</label><select id="from_id" name="from_id" class="form-select">@foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label" for="to_id">{{ __('To') }}</label><select id="to_id" name="to_id" class="form-select">@foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label" for="tamount">{{ __('Amount') }}</label><input id="tamount" name="amount" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label" for="date">{{ __('Date') }}</label><input type="date" id="date" name="date" class="form-control" required value="{{ now()->setTimezone('Asia/Dhaka')->toDateString() }}"></div>
            <div class="col-md-4"><label class="form-label" for="treference">{{ __('Reference') }}</label><input id="treference" name="reference" class="form-control"></div>
        </div><div class="card-footer"><button class="btn btn-primary">{{ __('Move') }}</button></div></form></div></div>@endcan
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Number') }}</th><th>{{ __('Date') }}</th><th>{{ __('Into') }}</th><th class="text-end">{{ __('Amount') }}</th><th>{{ __('Reference') }}</th></tr></thead><tbody>
        @forelse($rows as $d)<tr><td>{{ $d->number }}</td><td>{{ $d->deposit_date->format('d M Y') }}</td><td>{{ $d->bank?->name }}</td><td class="text-end">{{ $m($d->amount) }}</td><td>{{ $d->reference }}</td></tr>@empty<tr><td colspan="5" class="text-muted">{{ __('Nothing banked yet.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
