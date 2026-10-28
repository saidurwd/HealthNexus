@extends('layouts.adminlte')

@section('page_title', __('Payroll'))

@section('page_content')
    @include('admin.hr._flash')
    <div class="alert alert-warning small">{{ __('Pay rules (tax slabs, provident fund, overtime rate, leave) are examples until the accountant and HR manager confirm them. Check them before the first real payroll.') }}</div>
    @can('payroll.run.create')<form method="POST" action="{{ route('admin.hr.payroll.store') }}" class="card mb-3">@csrf<div class="card-body d-flex flex-wrap gap-2 align-items-center"><strong class="small">{{ __('Start a payroll run') }}</strong>
        <select name="month" class="form-select form-select-sm w-auto">@foreach($lastMonths as $m)<option value="{{ $m->format('Y-m') }}">{{ $m->format('F Y') }}</option>@endforeach</select>
        <select name="run_type" class="form-select form-select-sm w-auto"><option value="regular">{{ __('Regular') }}</option><option value="supplementary">{{ __('Correction (supplementary)') }}</option></select><input name="reason" class="form-control form-control-sm w-auto" placeholder="{{ __('For a correction: what') }}"><button class="btn btn-sm btn-danger">{{ __('Create and calculate') }}</button></div></form>@endcan
    <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>{{ __('Run') }}</th><th>{{ __('Month') }}</th><th>{{ __('Kind') }}</th><th class="text-end">{{ __('People') }}</th><th class="text-end">{{ __('Net') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>@forelse($runs as $r)<tr><td><a href="{{ route('admin.hr.payroll.show', $r) }}">{{ $r->number }}</a></td><td>{{ $r->period->month_start->format('F Y') }}</td><td>{{ $r->run_type === 'regular' ? __('Regular') : __('Correction') }}</td><td class="text-end">{{ $r->employee_count }}</td><td class="text-end">{{ $r->total_net }}</td><td><x-ui.status-badge :status="$r->status" /></td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('No payroll runs yet.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $runs->links() }}</div></div>
@stop
