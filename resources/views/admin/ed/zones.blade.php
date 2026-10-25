@extends('layouts.adminlte')

@section('page_title', __('Zones and bays'))

@section('page_content')
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('New zone') }}</h3></div>
        <form method="POST" action="{{ route('admin.ed.zones.store') }}" class="card-body row g-2">
            @csrf
            <div class="col-md-2"><input name="code" class="form-control" placeholder="{{ __('Code') }}" required maxlength="20"></div>
            <div class="col-md-4"><input name="name" class="form-control" placeholder="{{ __('Name') }}" required></div>
            <div class="col-md-3"><select name="zone_type" class="form-select">@foreach($types as $type)<option value="{{ $type }}">{{ __(\Illuminate\Support\Str::headline($type)) }}</option>@endforeach</select></div>
            <div class="col-md-1"><input type="number" name="sort_order" class="form-control" value="0" min="0"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('Create') }}</button></div>
        </form>
    </div>

    @forelse($zones as $zone)
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ $zone->name }} <small class="text-muted">({{ $zone->code }} · {{ __(\Illuminate\Support\Str::headline($zone->zone_type)) }})</small></h3></div>
            <div class="card-body">
                @foreach($zone->bays as $bay)
                    <form method="POST" action="{{ route('admin.ed.bays.status', $bay) }}" class="d-inline-flex align-items-center gap-1 me-2 mb-2">
                        @csrf @method('PUT')
                        <x-ui.status-badge :status="$bay->status" :label="$bay->code" />
                        @unless($bay->current_visit_id)
                            <select name="status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                                @foreach(['available', 'cleaning', 'blocked'] as $s)<option value="{{ $s }}" @selected($bay->status === $s)>{{ __(\Illuminate\Support\Str::headline($s)) }}</option>@endforeach
                            </select>
                        @endunless
                    </form>
                @endforeach
                <form method="POST" action="{{ route('admin.ed.zones.bays.store', $zone) }}" class="row g-2 mt-1">
                    @csrf
                    <div class="col-md-2"><input name="code" class="form-control form-control-sm" placeholder="{{ __('Bay code') }}" required maxlength="20"></div>
                    <div class="col-md-3"><input name="name" class="form-control form-control-sm" placeholder="{{ __('Name') }}"></div>
                    <div class="col-md-2"><button class="btn btn-sm btn-outline-primary">{{ __('Add bay') }}</button></div>
                </form>
            </div>
        </div>
    @empty
        <x-ui.empty-state :title="__('No zones yet.')" :hint="__('Create a triage area, a resuscitation room and the treatment bays first.')" />
    @endforelse
@stop
