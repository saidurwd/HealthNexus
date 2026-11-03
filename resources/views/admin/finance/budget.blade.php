@extends('layouts.adminlte')

@section('page_title', $b->name)

@section('page_content')
    @include('admin.finance._flash')
    @php($m = fn ($v) => number_format((float) $v, 2))
    <form method="GET" class="card card-body mb-3"><div class="row g-2 align-items-end"><div class="col-auto"><label class="form-label" for="through">{{ __('Months so far (1-12)') }}</label><input id="through" type="number" name="through" min="1" max="12" value="{{ $through }}" class="form-control"></div><div class="col-auto"><button class="btn btn-primary">{{ __('Show') }}</button></div>
        @if(auth()->user()->can('finance.report.export'))<div class="col-auto"><a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"><i class="bi bi-download"></i> {{ __('Download') }}</a></div>@endif
        <div class="col text-end"><x-ui.status-badge :status="$b->status" /> @can('finance.budget.approve')@if($b->status === 'draft')<form method="POST" action="{{ route('admin.finance.budgets.approve', $b->public_id) }}" class="d-inline">@csrf<button class="btn btn-success btn-sm">{{ __('Approve') }}</button></form>@endif @endcan</div></div></form>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Account') }}</th><th class="text-end">{{ __('Budget') }}</th><th class="text-end">{{ __('Actual') }}</th><th class="text-end">{{ __('Promised (open orders)') }}</th><th class="text-end">{{ __('Left / over') }}</th></tr></thead><tbody>
        @forelse($v['rows'] as $r)<tr><td>{{ $r['code'] }} {{ $r['name'] }}</td><td class="text-end">{{ $m($r['budget']) }}</td><td class="text-end">{{ $m($r['actual']) }}</td><td class="text-end">{{ bccomp($r['committed'], '0', 2) ? $m($r['committed']) : '' }}</td><td class="text-end {{ bccomp($r['variance'], '0', 2) < 0 ? 'text-danger' : 'text-success' }}">{{ $m($r['variance']) }}</td></tr>@empty<tr><td colspan="5" class="text-muted">{{ __('No lines yet.') }}</td></tr>@endforelse</tbody>
        <tfoot><tr class="fw-bold"><td>{{ __('Total') }}</td><td class="text-end">{{ $m($v['totals']['budget']) }}</td><td class="text-end">{{ $m($v['totals']['actual']) }}</td><td class="text-end">{{ $m($v['totals']['committed']) }}</td><td class="text-end">{{ $m($v['totals']['variance']) }}</td></tr></tfoot></table></div></div>
    @can('finance.budget.manage')@if($b->status === 'draft')<form method="POST" action="{{ route('admin.finance.budgets.lines', $b->public_id) }}" class="card mt-3"><div class="card-header"><h3 class="card-title">{{ __('Add or change a line') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-4"><label class="form-label" for="account_id">{{ __('Account') }}</label><select id="account_id" name="account_id" class="form-select">@foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->code }} {{ $a->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="cost_center_id">{{ __('Cost centre') }}</label><select id="cost_center_id" name="cost_center_id" class="form-select"><option value="">—</option>@foreach($centers as $c)<option value="{{ $c->id }}">{{ $c->code }} {{ $c->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label" for="period_number">{{ __('Month (0 = spread the amount over the year)') }}</label><input id="period_number" type="number" name="period_number" min="0" max="12" value="0" class="form-control"></div>
        <div class="col-md-2"><label class="form-label" for="amount">{{ __('Amount') }}</label><input id="amount" name="amount" class="form-control" required></div>
        <div class="col-md-1 d-flex align-items-end"><button class="btn btn-primary">{{ __('Save') }}</button></div></div></form>@endif @endcan
@stop
