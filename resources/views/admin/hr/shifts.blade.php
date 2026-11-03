@extends('layouts.adminlte')

@section('page_title', __('Shifts and staffing needs'))

@section('page_content')
    @include('admin.hr._flash')
    <div class="row g-3">
        <div class="col-lg-6">
            @can('hr.shift.manage')<form method="POST" action="{{ route('admin.hr.shifts.store') }}" class="card">@csrf<div class="card-header"><h3 class="card-title">{{ __('Add a shift') }}</h3></div>
                <div class="card-body row g-2"><div class="col-md-3"><input name="code" class="form-control" placeholder="{{ __('Code') }}" required></div><div class="col-md-5"><input name="name" class="form-control" placeholder="{{ __('Name') }}" required></div>
                    <div class="col-md-2"><input type="time" name="start_time" class="form-control" required></div><div class="col-md-2"><input type="time" name="end_time" class="form-control" required></div>
                    <div class="col-md-4"><input type="number" name="break_minutes" class="form-control" placeholder="{{ __('Break (min)') }}"></div><div class="col-md-4"><input type="number" name="grace_minutes" class="form-control" placeholder="{{ __('Grace (min)') }}"></div><div class="col-md-4"><button class="btn btn-danger w-100">{{ __('Add') }}</button></div></div></form>@endcan
            <div class="card"><ul class="list-group list-group-flush small">@forelse($shifts as $s)<li class="list-group-item d-flex justify-content-between"><span>{{ $s->code }} · {{ $s->name }}</span><span>{{ substr($s->start_time, 0, 5) }} – {{ substr($s->end_time, 0, 5) }} @if($s->is_night)<span class="badge text-bg-dark">{{ __('Night') }}</span>@endif</span></li>@empty<li class="list-group-item text-muted">{{ __('No shifts yet.') }}</li>@endforelse</ul></div>
        </div>
        <div class="col-lg-6">
            @can('hr.shift.manage')<form method="POST" action="{{ route('admin.hr.staffing.store') }}" class="card">@csrf<div class="card-header"><h3 class="card-title">{{ __('Minimum people needed') }}</h3></div>
                <div class="card-body row g-2"><div class="col-md-4"><select name="department_id" class="form-select" required>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div><div class="col-md-3"><select name="shift_id" class="form-select" required>@foreach($shifts as $s)<option value="{{ $s->id }}">{{ $s->code }}</option>@endforeach</select></div>
                    <div class="col-md-3"><select name="job_id" class="form-select"><option value="">{{ __('Any job') }}</option>@foreach($jobs as $j)<option value="{{ $j->id }}">{{ $j->title }}</option>@endforeach</select></div><div class="col-md-1"><input type="number" name="min_count" min="1" class="form-control" required></div><div class="col-md-1"><button class="btn btn-danger">{{ __('Add') }}</button></div></div></form>@endcan
            <div class="card"><ul class="list-group list-group-flush small">@forelse($requirements as $r)<li class="list-group-item">{{ $departments->firstWhere('id', $r->department_id)?->name }} · {{ $r->shift->code }} · {{ __('at least') }} {{ $r->min_count }}</li>@empty<li class="list-group-item text-muted">{{ __('No staffing needs set.') }}</li>@endforelse</ul></div>
        </div>
    </div>
@stop
