@extends('layouts.adminlte')

@section('page_title', __('Leave requests'))

@section('page_content')
    @include('admin.hr._flash')
    <form method="GET" class="mb-3"><select name="status" class="form-select w-auto" onchange="this.form.submit()"><option value="">{{ __('Waiting first') }}</option>@foreach(['submitted', 'manager_approved', 'approved', 'rejected', 'cancelled'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ __(\Illuminate\Support\Str::headline($s)) }}</option>@endforeach</select></form>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Person') }}</th><th>{{ __('Leave') }}</th><th>{{ __('From') }}</th><th>{{ __('To') }}</th><th class="text-end">{{ __('Days') }}</th><th>{{ __('Why') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
        <tbody>@forelse($rows as $l)<tr><td>{{ $l->employee->fullName() }}</td><td>{{ $l->type->name }}</td><td>{{ $l->from_date->toDateString() }}</td><td>{{ $l->to_date->toDateString() }}</td><td class="text-end">{{ $l->days + 0 }}</td><td>{{ $l->reason }}</td><td><x-ui.status-badge :status="$l->status" /></td>
            <td>@if(in_array($l->status, ['submitted', 'manager_approved']) && $l->employee_id !== $myEmployeeId)<form method="POST" action="{{ route('admin.hr.leave.decide', $l) }}" class="d-flex gap-1">@csrf
                <select name="as" class="form-select form-select-sm w-auto">@if($canManager && $l->status === 'submitted')<option value="manager">{{ __('As manager') }}</option>@endif @if($canHr)<option value="hr">{{ __('As HR') }}</option>@endif</select><input name="note" class="form-control form-control-sm" placeholder="{{ __('Note') }}"><button name="decision" value="approve" class="btn btn-sm btn-outline-success">{{ __('Approve') }}</button><button name="decision" value="reject" class="btn btn-sm btn-outline-danger">{{ __('Reject') }}</button></form>@endif</td></tr>
        @empty<tr><td colspan="8" class="text-muted">{{ __('No requests.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
