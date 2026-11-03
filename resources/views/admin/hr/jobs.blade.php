@extends('layouts.adminlte')

@section('page_title', __('Jobs and positions'))

@section('page_content')
    @include('admin.hr._flash')
    @can('hr.job.manage')
    <div class="row g-3 mb-3">
        <div class="col-lg-6"><form method="POST" action="{{ route('admin.hr.jobs.store') }}" class="card h-100">@csrf<div class="card-header"><h3 class="card-title">{{ __('Add a job') }}</h3></div>
            <div class="card-body row g-2"><div class="col-md-4"><input name="code" class="form-control" placeholder="{{ __('Code') }}" required></div><div class="col-md-8"><input name="title" class="form-control" placeholder="{{ __('Title') }}" required></div>
                <div class="col-md-5"><select name="family" class="form-select">@foreach($families as $f)<option value="{{ $f }}">{{ __(\Illuminate\Support\Str::headline($f)) }}</option>@endforeach</select></div><div class="col-md-3"><input name="grade" class="form-control" placeholder="{{ __('Grade') }}"></div>
                <div class="col-12"><label class="form-label small">{{ __('Licences this job must hold') }}</label><div class="d-flex flex-wrap gap-3">@foreach($credentialTypes as $t)<label class="form-check"><input class="form-check-input" type="checkbox" name="required_credentials[]" value="{{ $t }}"> {{ strtoupper($t) }}</label>@endforeach</div></div>
                <div class="col-12"><button class="btn btn-danger">{{ __('Add') }}</button></div></div></form></div>
        <div class="col-lg-6"><form method="POST" action="{{ route('admin.hr.positions.store') }}" class="card h-100">@csrf<div class="card-header"><h3 class="card-title">{{ __('Add a position (seats in a department)') }}</h3></div>
            <div class="card-body row g-2"><div class="col-md-6"><select name="job_id" class="form-select" required>@foreach($jobs as $j)<option value="{{ $j->id }}">{{ $j->title }}</option>@endforeach</select></div><div class="col-md-6"><select name="department_id" class="form-select"><option value="">{{ __('No department') }}</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><input name="code" class="form-control" placeholder="{{ __('Code') }}" required></div><div class="col-md-6"><input name="title" class="form-control" placeholder="{{ __('Title') }}" required></div><div class="col-md-3"><input type="number" min="1" name="headcount" value="1" class="form-control" title="{{ __('Seats') }}"></div>
                <div class="col-12"><button class="btn btn-danger">{{ __('Add') }}</button></div></div></form></div>
    </div>
    @endcan
    <div class="row g-3"><div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Jobs') }}</h3></div>
        <ul class="list-group list-group-flush small">@forelse($jobs as $j)<li class="list-group-item d-flex justify-content-between"><span>{{ $j->code }} · {{ $j->title }} <span class="text-muted">{{ __(\Illuminate\Support\Str::headline($j->family)) }}</span></span><span>{{ collect($j->required_credentials)->map(fn ($c) => strtoupper($c))->implode(', ') }}</span></li>@empty<li class="list-group-item text-muted">{{ __('No jobs yet.') }}</li>@endforelse</ul></div></div>
        <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Positions') }}</h3></div>
        <ul class="list-group list-group-flush small">@forelse($positions as $p)<li class="list-group-item d-flex justify-content-between"><span>{{ $p->code }} · {{ $p->title }}</span><span class="{{ $p->filled > $p->headcount ? 'text-danger' : '' }}">{{ $p->filled }} / {{ $p->headcount }}</span></li>@empty<li class="list-group-item text-muted">{{ __('No positions yet.') }}</li>@endforelse</ul></div></div></div>
@stop
