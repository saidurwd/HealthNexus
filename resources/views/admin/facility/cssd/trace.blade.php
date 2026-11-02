@extends('layouts.adminlte')

@section('page_title', __('Packs used in an operation'))

@section('page_content')
    <form class="row g-2 mb-3" method="GET"><div class="col-md-4"><input name="surgery" class="form-control form-control-sm" placeholder="{{ __('Operation number') }}" value="{{ $query }}"></div><div class="col-md-2"><button class="btn btn-sm btn-primary">{{ __('Find') }}</button></div></form>
    @if($query !== '' && ! $surgery)<div class="alert alert-warning">{{ __('No operation has that number.') }}</div>@endif
    @if($surgery)<div class="card"><div class="card-header"><h3 class="card-title">{{ $surgery->surgery_number }}</h3></div><ul class="list-group list-group-flush small">
        @forelse($packs as $p)<li class="list-group-item d-flex justify-content-between"><span>{{ $p->label }} · {{ $p->set->name }}</span><span class="text-muted">{{ __('batch') }} <a href="{{ route('admin.facility.cssd.show', $p->batch->public_id) }}">{{ $p->batch->number }}</a> · {{ strtoupper($p->batch->method) }} · {{ $p->batch->ended_at?->format('d M H:i') }}</span></li>@empty<li class="list-group-item text-muted">{{ __('No pack is recorded for this operation.') }}</li>@endforelse</ul></div>@endif
@stop
