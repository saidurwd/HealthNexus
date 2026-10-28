@extends('layouts.adminlte')

@section('page_title', __('Employees'))

@section('page_content')
    @include('admin.hr._flash')
    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control w-auto" placeholder="{{ __('Number or name') }}">
        <select name="status" class="form-select w-auto"><option value="">{{ __('Working or joining') }}</option>@foreach($statuses as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ __(\Illuminate\Support\Str::headline($s)) }}</option>@endforeach</select>
        <select name="department" class="form-select w-auto"><option value="">{{ __('Any department') }}</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department') == $d->id)>{{ $d->name }}</option>@endforeach</select>
        <button class="btn btn-outline-secondary">{{ __('Filter') }}</button>
    </form>
    @can('hr.employee.manage')
    <form method="POST" action="{{ route('admin.hr.employees.store') }}" class="card mb-3">@csrf
        <div class="card-header"><h3 class="card-title">{{ __('Add an employee') }}</h3></div>
        <div class="card-body row g-2">
            <div class="col-md-2"><input name="first_name" class="form-control" placeholder="{{ __('First name') }}" required maxlength="100"></div>
            <div class="col-md-2"><input name="last_name" class="form-control" placeholder="{{ __('Last name') }}" maxlength="100"></div>
            <div class="col-md-1"><select name="gender" class="form-select"><option value="">—</option><option value="male">{{ __('Male') }}</option><option value="female">{{ __('Female') }}</option><option value="other">{{ __('Other') }}</option></select></div>
            <div class="col-md-2"><input type="date" name="joined_on" class="form-control" required title="{{ __('Joining date') }}"></div>
            <div class="col-md-2"><select name="department_id" class="form-select"><option value="">{{ __('No department') }}</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><select name="job_id" class="form-select"><option value="">{{ __('No job') }}</option>@foreach($jobs as $j)<option value="{{ $j->id }}">{{ $j->title }}</option>@endforeach</select></div>
            <div class="col-md-1"><select name="category" class="form-select">@foreach($categories as $c)<option value="{{ $c }}">{{ __(\Illuminate\Support\Str::headline($c)) }}</option>@endforeach</select></div>
            <div class="col-md-2"><select name="manager_id" class="form-select"><option value="">{{ __('No manager') }}</option>@foreach($managers as $m)<option value="{{ $m->id }}">{{ $m->first_name }} {{ $m->last_name }}</option>@endforeach</select></div>
            <div class="col-md-2"><input name="phone" class="form-control" placeholder="{{ __('Phone') }}"></div>
            <div class="col-md-2"><input name="device_code" class="form-control" placeholder="{{ __('Attendance device number') }}"></div>
            <div class="col-md-2"><button class="btn btn-danger w-100">{{ __('Add') }}</button></div>
        </div></form>
    @endcan
    <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>{{ __('No.') }}</th><th>{{ __('Name') }}</th><th>{{ __('Department') }}</th><th>{{ __('Job') }}</th><th>{{ __('Joined') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>@forelse($rows as $e)<tr><td>{{ $e->employee_no }}</td><td><a href="{{ route('admin.hr.employees.show', $e) }}">{{ $e->fullName() }}</a></td><td>{{ $e->department?->name }}</td><td>{{ $e->job?->title }}</td><td>{{ $e->joined_on?->toDateString() }}</td><td><x-ui.status-badge :status="$e->status" /></td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('No employees.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
