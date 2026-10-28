@extends('layouts.adminlte')

@section('page_title', __('Training and appraisals'))

@section('page_content')
    @include('admin.hr._flash')
    <div class="row g-3">
        <div class="col-lg-6">
            <form method="POST" action="{{ route('admin.hr.training.store') }}" class="card">@csrf<div class="card-header"><h3 class="card-title">{{ __('Add a training') }}</h3></div>
                <div class="card-body row g-2"><div class="col-md-6"><input name="title" class="form-control" placeholder="{{ __('Title') }}" required></div><div class="col-md-3"><input type="date" name="held_on" class="form-control" required></div><div class="col-md-3"><input type="number" name="valid_months" class="form-control" placeholder="{{ __('Valid months') }}" min="1"></div>
                    <div class="col-md-6"><label class="form-check"><input class="form-check-input" type="checkbox" name="mandatory" value="1"> {{ __('Everyone must have it') }}</label></div><div class="col-md-6"><button class="btn btn-danger">{{ __('Add') }}</button></div></div></form>
            @foreach($trainings as $t)<div class="card"><div class="card-header"><h3 class="card-title">{{ $t->title }} <small class="text-muted">{{ $t->held_on->toDateString() }} · {{ $t->records_count }} {{ __('people') }}</small> @if($t->mandatory)<span class="badge text-bg-warning">{{ __('Mandatory') }}</span>@endif</h3></div>
                <form method="POST" action="{{ route('admin.hr.training.record', $t) }}" class="card-body d-flex gap-2">@csrf<select name="employee" class="form-select form-select-sm">@foreach($employees as $e)<option value="{{ $e->public_id }}">{{ $e->employee_no }} {{ $e->first_name }} {{ $e->last_name }}</option>@endforeach</select><select name="outcome" class="form-select form-select-sm w-auto"><option value="attended">{{ __('Attended') }}</option><option value="passed">{{ __('Passed') }}</option><option value="failed">{{ __('Failed') }}</option><option value="absent">{{ __('Absent') }}</option></select><button class="btn btn-sm btn-outline-primary">{{ __('Record') }}</button></form></div>@endforeach
        </div>
        <div class="col-lg-6">
            <form method="POST" action="{{ route('admin.hr.cycles.store') }}" class="card">@csrf<div class="card-header"><h3 class="card-title">{{ __('Open an appraisal cycle') }}</h3></div>
                <div class="card-body row g-2"><div class="col-md-5"><input name="name" class="form-control" placeholder="{{ __('Name, e.g. 2026 yearly') }}" required></div><div class="col-md-3"><input type="date" name="period_start" class="form-control" required></div><div class="col-md-3"><input type="date" name="period_end" class="form-control" required></div><div class="col-md-1"><button class="btn btn-danger">{{ __('Open') }}</button></div></div></form>
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Appraisals waiting for a rating') }}</h3></div>
                <ul class="list-group list-group-flush small">@forelse($appraisals as $a)<li class="list-group-item"><div class="fw-semibold">{{ $a->employee->fullName() }} <span class="text-muted">{{ $a->cycle->name }}</span></div>
                    <form method="POST" action="{{ route('admin.hr.appraisals.submit', $a) }}" class="d-flex gap-2 mt-1">@csrf<select name="rating" class="form-select form-select-sm w-auto">@foreach([5, 4, 3, 2, 1] as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach</select><input name="strengths" class="form-control form-control-sm" placeholder="{{ __('Strengths') }}"><input name="improvements" class="form-control form-control-sm" placeholder="{{ __('To improve') }}"><button class="btn btn-sm btn-outline-primary">{{ __('Submit') }}</button></form></li>@empty<li class="list-group-item text-muted">{{ __('Nothing waiting.') }}</li>@endforelse</ul></div>
        </div>
    </div>
@stop
