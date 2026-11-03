@extends('layouts.adminlte')

@section('page_title', __('Where things are posted'))

@section('page_content')
    @include('admin.finance._flash')
    <p class="text-muted small">{{ __('Each kind of posting names its account here: which account takes the revenue of a Billing category, which account receives cash, mobile-wallet or card payments, where VAT goes. These are the rules the posting engine follows; change a row and new postings follow it. A role without an account is reported, never guessed.') }}</p>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Role') }}</th><th>{{ __('Applies to') }}</th><th>{{ __('Account') }}</th></tr></thead><tbody>
        @foreach($roles as $r)<tr><td>{{ $r->role }}</td><td>{{ $r->scope_key === 'all' ? __('Everything else') : $r->scope_key }}</td><td>{{ $r->account?->code }} {{ $r->account?->name }}</td></tr>@endforeach</tbody></table></div></div>
    @can('finance.chart.manage')<form method="POST" action="{{ route('admin.finance.roles.save') }}" class="card mt-3"><div class="card-header"><h3 class="card-title">{{ __('Set or change a role') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-3"><label class="form-label" for="role">{{ __('Role') }}</label><input id="role" name="role" list="roles" class="form-control" required placeholder="revenue"><datalist id="roles">@foreach($roles->pluck('role')->unique() as $x)<option value="{{ $x }}">@endforeach<option value="payment_method"></datalist></div>
        <div class="col-md-3"><label class="form-label" for="scope_key">{{ __('Applies to (all, a Billing category code, a payment method code...)') }}</label><input id="scope_key" name="scope_key" class="form-control" value="all" required></div>
        <div class="col-md-4"><label class="form-label" for="account_id">{{ __('Account') }}</label><select id="account_id" name="account_id" class="form-select">@foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->code }} {{ $a->name }}</option>@endforeach</select></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary">{{ __('Save') }}</button></div></div></form>@endcan
@stop
