@extends('layouts.adminlte')

@section('page_title', __('Patient logins'))

@section('page_content')
    @include('admin.portal._flash')
    @if($code)<div class="alert alert-warning"><strong>{{ __('Invitation code') }}:</strong> <span class="fs-4 font-monospace">{{ $code }}</span><div class="small">{{ __('Give this to the patient after you have checked who they are. They enter it with their date of birth. It is not shown again.') }}</div></div>@endif
    <div class="row g-3 mb-3">
        <div class="col-md-8"><form method="GET" class="d-flex gap-2"><input name="q" value="{{ $q }}" class="form-control" placeholder="{{ __('Patient number, name or phone') }}"><select name="status" class="form-select w-auto"><option value="">{{ __('All') }}</option>@foreach(['active', 'invited', 'pending', 'locked', 'suspended', 'closed'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ __(\Illuminate\Support\Str::headline($s)) }}</option>@endforeach</select><button class="btn btn-outline-primary">{{ __('Search') }}</button></form></div>
        @can('portal.account.manage')<div class="col-md-4"><form method="POST" action="{{ route('admin.portal.accounts.invite') }}" class="d-flex gap-2">@csrf<input name="patient_no" class="form-control" placeholder="{{ __('Patient number') }}" required><button class="btn btn-primary">{{ __('Invite') }}</button></form></div>@endcan
    </div>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Patient') }}</th><th>{{ __('Phone') }}</th><th>{{ __('Status') }}</th><th>{{ __('Last sign-in') }}</th><th>{{ __('Joined') }}</th><th></th></tr></thead>
        <tbody>@forelse($rows as $a)<tr><td>{{ $a->patient?->first_name }} {{ $a->patient?->last_name }} <span class="text-muted">{{ $a->patient?->enterprise_patient_no }}</span></td><td>{{ \App\Services\Portal\PhoneNumber::mask($a->phone) }}</td><td><x-ui.status-badge :status="$a->status" /></td><td>{{ $a->last_login_at?->diffForHumans() ?? '—' }}</td><td>{{ $a->activated_at?->toDateString() ?? '—' }}</td><td><a href="{{ route('admin.portal.accounts.show', $a->public_id) }}">{{ __('Open') }}</a></td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('No logins.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
