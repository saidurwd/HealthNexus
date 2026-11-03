@extends('layouts.adminlte')

@section('page_title', __('Overtime'))

@section('page_content')
    @include('admin.hr._flash')
    <form method="POST" action="{{ route('admin.hr.overtime.sync') }}" class="card mb-3">@csrf<div class="card-body d-flex flex-wrap gap-2 align-items-center"><strong class="small">{{ __('Find overtime in attendance') }}</strong><input type="date" name="from" class="form-control form-control-sm w-auto" required><input type="date" name="to" class="form-control form-control-sm w-auto" required><button class="btn btn-sm btn-outline-primary">{{ __('Find') }}</button></div></form>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Person') }}</th><th>{{ __('Day') }}</th><th>{{ __('Kind of day') }}</th><th class="text-end">{{ __('Claimed (min)') }}</th><th class="text-end">{{ __('Approved (min)') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
        <tbody>@forelse($rows as $o)<tr><td>{{ $o->employee->fullName() }}</td><td>{{ $o->work_date->toDateString() }}</td><td>{{ __(\Illuminate\Support\Str::headline($o->day_type)) }}</td><td class="text-end">{{ $o->minutes_claimed }}</td><td class="text-end">{{ $o->minutes_approved ?: '' }}</td><td><x-ui.status-badge :status="$o->status" /></td>
            <td>@if($o->status === 'pending')<form method="POST" action="{{ route('admin.hr.overtime.decide', $o) }}" class="d-flex gap-1">@csrf<input type="number" name="minutes" class="form-control form-control-sm" style="width:90px" placeholder="{{ __('minutes') }}"><button name="decision" value="approve" class="btn btn-sm btn-outline-success">{{ __('Approve') }}</button><button name="decision" value="reject" class="btn btn-sm btn-outline-danger">{{ __('Reject') }}</button></form>@endif</td></tr>
        @empty<tr><td colspan="7" class="text-muted">{{ __('No overtime claims.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
