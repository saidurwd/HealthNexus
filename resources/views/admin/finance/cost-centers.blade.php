@extends('layouts.adminlte')

@section('page_title', __('Cost centres'))

@section('page_content')
    @include('admin.finance._flash')
    <p class="text-muted small">{{ __('A cost centre is a place costs and revenue are traced to: a department, a branch or a service line. A "profit" centre earns revenue; a "cost" centre only spends.') }}</p>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Kind') }}</th><th>{{ __('Active') }}</th></tr></thead><tbody>
        @forelse($rows as $c)<tr><td>{{ $c->code }}</td><td>{{ $c->name }}</td><td>{{ $c->kind === 'profit' ? __('Profit') : __('Cost') }}</td><td>{{ $c->active ? __('Yes') : __('No') }}</td></tr>@empty<tr><td colspan="4" class="text-muted">{{ __('None yet.') }}</td></tr>@endforelse</tbody></table></div></div>
    @can('finance.chart.manage')<form method="POST" action="{{ route('admin.finance.cost-centers.store') }}" class="card mt-3"><div class="card-body row g-2">@csrf
        <div class="col-md-2"><label class="form-label" for="code">{{ __('Code') }}</label><input id="code" name="code" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label" for="name">{{ __('Name') }}</label><input id="name" name="name" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label" for="kind">{{ __('Kind') }}</label><select id="kind" name="kind" class="form-select"><option value="profit">{{ __('Profit') }}</option><option value="cost">{{ __('Cost') }}</option></select></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary">{{ __('Add') }}</button></div></div></form>@endcan
@stop
