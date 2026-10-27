@extends('layouts.adminlte')

@section('page_title', __('Stores'))

@section('page_content')
    @include('admin.inventory._flash')
    @can('inventory.store.manage')
    <form method="POST" action="{{ route('admin.inventory.stores.store') }}" class="card mb-3">@csrf
        <div class="card-header"><h3 class="card-title">{{ __('Add a store') }}</h3></div>
        <div class="card-body row g-2">
            <div class="col-md-2"><input name="code" class="form-control" placeholder="{{ __('Code') }}" required maxlength="30"></div>
            <div class="col-md-3"><input name="name" class="form-control" placeholder="{{ __('Name') }}" required maxlength="120"></div>
            <div class="col-md-2"><select name="kind" class="form-select">@foreach($kinds as $k)<option value="{{ $k }}">{{ __(\Illuminate\Support\Str::headline($k)) }}</option>@endforeach</select></div>
            <div class="col-md-3"><select name="department_id" class="form-select"><option value="">{{ __('No department') }}</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-check"><input class="form-check-input" type="checkbox" name="allow_negative" value="1"> {{ __('May go below zero') }}</label></div>
            <div class="col-12"><button class="btn btn-danger">{{ __('Add') }}</button></div>
        </div></form>
    @endcan
    @foreach($stores as $s)
        <div class="card"><div class="card-header"><h3 class="card-title">{{ $s->name }} <small class="text-muted">{{ $s->code }} · {{ __(\Illuminate\Support\Str::headline($s->kind)) }}</small></h3></div>
            <div class="card-body small">@forelse($s->locations as $l)<span class="badge text-bg-light me-1">{{ $l->code }} · {{ $l->name }}</span>@empty<span class="text-muted">{{ __('No shelves or bins defined.') }}</span>@endforelse</div>
            @can('inventory.store.manage')<form method="POST" action="{{ route('admin.inventory.stores.locations', $s) }}" class="card-footer d-flex gap-2">@csrf<input name="code" class="form-control form-control-sm w-auto" placeholder="{{ __('Code') }}" required><input name="name" class="form-control form-control-sm w-auto" placeholder="{{ __('Shelf or bin') }}" required><button class="btn btn-sm btn-outline-primary">{{ __('Add') }}</button></form>@endcan
        </div>
    @endforeach
@stop
