@extends('layouts.adminlte')

@section('page_title', __('Leave balances'))

@section('page_content')
    @include('admin.hr._flash')
    <div class="row g-3 mb-3">
        @can('leave.accrue')<div class="col-lg-6"><form method="POST" action="{{ route('admin.hr.leave.accrue') }}" class="card h-100">@csrf<div class="card-header"><h3 class="card-title">{{ __('Yearly and monthly jobs') }}</h3></div><div class="card-body d-flex flex-wrap gap-2">
            <select name="action" class="form-select form-select-sm w-auto"><option value="open_year">{{ __('Give the year\'s entitlement') }}</option><option value="accrue_month">{{ __('Add a month of earned leave') }}</option><option value="roll_year">{{ __('Close the year (carry forward, lapse)') }}</option></select>
            <input type="number" name="year" value="{{ now()->year }}" class="form-control form-control-sm" style="width:90px"><input type="number" name="month" min="1" max="12" value="{{ now()->month }}" class="form-control form-control-sm" style="width:70px"><button class="btn btn-sm btn-outline-primary">{{ __('Run') }}</button></div><div class="card-footer small text-muted">{{ __('Safe to run again: nothing is added twice.') }}</div></form></div>@endcan
        @can('leave.balance.adjust')<div class="col-lg-6"><form method="POST" action="{{ route('admin.hr.leave.adjust') }}" class="card h-100">@csrf<div class="card-header"><h3 class="card-title">{{ __('Correct a balance') }}</h3></div><div class="card-body d-flex flex-wrap gap-2">
            <select name="employee" class="form-select form-select-sm w-auto">@foreach($allEmployees as $e)<option value="{{ $e->public_id }}">{{ $e->employee_no }} {{ $e->first_name }}</option>@endforeach</select><select name="leave_type_id" class="form-select form-select-sm w-auto">@foreach($types as $t)<option value="{{ $t->id }}">{{ $t->code }}</option>@endforeach</select>
            <input type="number" step="0.5" name="days" class="form-control form-control-sm" style="width:90px" placeholder="{{ __('± days') }}" required><input name="reason" class="form-control form-control-sm" placeholder="{{ __('Reason') }}" required><button class="btn btn-sm btn-outline-primary">{{ __('Save') }}</button></div></form></div>@endcan
    </div>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Person') }}</th>@foreach($types as $t)<th class="text-end">{{ $t->code }}</th>@endforeach</tr></thead>
        <tbody>@foreach($employees as $e)<tr><td>{{ $e->employee_no }} {{ $e->first_name }} {{ $e->last_name }}</td>@foreach($types as $t)<td class="text-end">{{ number_format((float) (($sums[$e->id] ?? collect())->firstWhere('leave_type_id', $t->id)->d ?? 0), 1) }}</td>@endforeach</tr>@endforeach</tbody></table></div><div class="card-footer">{{ $employees->links() }}</div></div>
@stop
