@extends('layouts.adminlte')

@section('page_title', __('Department purchasing budgets'))

@section('page_content')
    @include('admin.procurement._flash')
    @can('procurement.budget.manage')
    <form method="POST" action="{{ route('admin.procurement.budgets.store') }}" class="card mb-3">@csrf<div class="card-body row g-2">
        <div class="col-md-3"><select name="department_id" class="form-select" required>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><input type="date" name="period_start" class="form-control" required></div><div class="col-md-2"><input type="date" name="period_end" class="form-control" required></div>
        <div class="col-md-3"><input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="{{ __('Budget') }}" required></div><div class="col-md-2"><button class="btn btn-danger w-100">{{ __('Save') }}</button></div></div></form>
    @endcan
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Department') }}</th><th>{{ __('Period') }}</th><th class="text-end">{{ __('Budget') }}</th><th class="text-end">{{ __('Committed') }}</th><th class="text-end">{{ __('Left') }}</th></tr></thead>
        <tbody>@forelse($rows as $b)<tr><td>{{ $departments->firstWhere('id', $b->department_id)?->name }}</td><td>{{ $b->period_start->toDateString() }} → {{ $b->period_end->toDateString() }}</td><td class="text-end">{{ $b->amount }}</td><td class="text-end">{{ $b->committed }}</td><td class="text-end {{ bccomp((string) $b->amount, $b->committed, 2) < 0 ? 'text-danger' : '' }}">{{ bcsub((string) $b->amount, $b->committed, 2) }}</td></tr>
        @empty<tr><td colspan="5" class="text-muted">{{ __('No budgets set; spending is not limited.') }}</td></tr>@endforelse</tbody></table></div></div>
@stop
