@extends('layouts.adminlte')

@section('page_title', __('Attendance corrections'))

@section('page_content')
    @include('admin.hr._flash')
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Person') }}</th><th>{{ __('Day') }}</th><th>{{ __('Says in') }}</th><th>{{ __('Says out') }}</th><th>{{ __('Why') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
        <tbody>@forelse($rows as $r)<tr><td>{{ $r->employee->fullName() }}</td><td>{{ $r->work_date->toDateString() }}</td><td>{{ $r->requested_in?->format('H:i') }}</td><td>{{ $r->requested_out?->format('H:i') }}</td><td>{{ $r->reason }}</td><td><x-ui.status-badge :status="$r->status" /></td>
            <td>@if($r->status === 'requested')<form method="POST" action="{{ route('admin.hr.corrections.decide', $r) }}" class="d-flex gap-1">@csrf<input name="note" class="form-control form-control-sm" placeholder="{{ __('Note') }}"><button name="decision" value="approve" class="btn btn-sm btn-outline-success">{{ __('Approve') }}</button><button name="decision" value="reject" class="btn btn-sm btn-outline-danger">{{ __('Reject') }}</button></form>@endif</td></tr>
        @empty<tr><td colspan="7" class="text-muted">{{ __('No corrections.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
