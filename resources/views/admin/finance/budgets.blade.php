@extends('layouts.adminlte')

@section('page_title', __('Budgets'))

@section('page_content')
    @include('admin.finance._flash')
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Budget') }}</th><th>{{ __('Year') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
        @forelse($rows as $b)<tr><td><a href="{{ route('admin.finance.budgets.show', $b->public_id) }}">{{ $b->name }}</a></td><td>{{ $yearNames[$b->fiscal_year_id] ?? '' }}</td><td><x-ui.status-badge :status="$b->status" /></td></tr>@empty<tr><td colspan="3" class="text-muted">{{ __('No budgets yet.') }}</td></tr>@endforelse</tbody></table></div></div>
    @can('finance.budget.manage')<form method="POST" action="{{ route('admin.finance.budgets.store') }}" class="card mt-3"><div class="card-body row g-2">@csrf
        <div class="col-md-3"><label class="form-label" for="fiscal_year_id">{{ __('Fiscal year') }}</label><select id="fiscal_year_id" name="fiscal_year_id" class="form-select">@foreach($years as $y)<option value="{{ $y->id }}">{{ $y->name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label" for="name">{{ __('Name') }}</label><input id="name" name="name" class="form-control" required></div>
        <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary">{{ __('Create') }}</button></div></div></form>@endcan
    <form method="POST" action="{{ route('admin.finance.budgets.check') }}" class="card mt-3"><div class="card-header"><h3 class="card-title">{{ __('Check a purchase against the budget') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-3"><label class="form-label" for="account">{{ __('Expense account code') }}</label><input id="account" name="account" class="form-control" value="5200" required></div>
        <div class="col-md-3"><label class="form-label" for="amount">{{ __('Amount') }}</label><input id="amount" name="amount" class="form-control" required></div>
        <div class="col-md-3 d-flex align-items-end"><button class="btn btn-outline-primary">{{ __('Check') }}</button></div></div></form>
@stop
