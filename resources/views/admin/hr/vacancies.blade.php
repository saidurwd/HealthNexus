@extends('layouts.adminlte')

@section('page_title', __('Vacancies and candidates'))

@section('page_content')
    @include('admin.hr._flash')
    <form method="POST" action="{{ route('admin.hr.vacancies.store') }}" class="card mb-3">@csrf<div class="card-header"><h3 class="card-title">{{ __('Open a vacancy') }}</h3></div>
        <div class="card-body row g-2"><div class="col-md-4"><input name="title" class="form-control" placeholder="{{ __('Title') }}" required></div><div class="col-md-4"><select name="position_id" class="form-select"><option value="">{{ __('No position') }}</option>@foreach($positions as $p)<option value="{{ $p->id }}">{{ $p->code }} {{ $p->title }}</option>@endforeach</select></div>
            <div class="col-md-1"><input type="number" name="openings" value="1" min="1" class="form-control"></div><div class="col-md-2"><input type="date" name="closes_on" class="form-control"></div><div class="col-md-1"><button class="btn btn-danger w-100">{{ __('Open') }}</button></div></div></form>
    @forelse($vacancies as $v)
        <div class="card"><div class="card-header d-flex justify-content-between"><h3 class="card-title">{{ $v->title }} <small class="text-muted">{{ $v->openings }} {{ __('opening(s)') }}</small></h3><x-ui.status-badge :status="$v->status" /></div>
            <ul class="list-group list-group-flush small">@forelse($v->candidates as $c)<li class="list-group-item d-flex justify-content-between align-items-center gap-2"><span>{{ $c->name }} {{ $c->phone }}</span>
                <span class="d-flex gap-1 align-items-center"><x-ui.status-badge :status="$c->stage" />
                    @if(! in_array($c->stage, ['hired', 'rejected', 'withdrawn']))<form method="POST" action="{{ route('admin.hr.candidates.move', $c) }}" class="d-flex gap-1">@csrf
                        @php($next = ['applied' => 'screened', 'screened' => 'interview', 'interview' => 'offered', 'offered' => 'hired'][$c->stage])
                        @if($next === 'hired')<input type="date" name="joined_on" class="form-control form-control-sm" required>@endif<button name="stage" value="{{ $next }}" class="btn btn-sm btn-outline-primary">{{ $next === 'hired' ? __('Hire') : __(\Illuminate\Support\Str::headline($next)) }}</button><button name="stage" value="rejected" class="btn btn-sm btn-outline-secondary">{{ __('Reject') }}</button></form>@endif</span></li>
            @empty<li class="list-group-item text-muted">{{ __('No candidates yet.') }}</li>@endforelse</ul>
            @if($v->status === 'open')<form method="POST" action="{{ route('admin.hr.candidates.store', $v) }}" class="card-footer d-flex gap-2">@csrf<input name="name" class="form-control form-control-sm w-auto" placeholder="{{ __('Name') }}" required><input name="phone" class="form-control form-control-sm w-auto" placeholder="{{ __('Phone') }}"><button class="btn btn-sm btn-outline-primary">{{ __('Add candidate') }}</button></form>@endif</div>
    @empty<div class="alert alert-info">{{ __('No vacancies.') }}</div>@endforelse
@stop
