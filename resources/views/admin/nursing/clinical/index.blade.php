@extends('layouts.adminlte')

@section('page_title', 'Devices, IV, Wounds & Education')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Lines / Tubes / Devices') }}</h3></div>
        <div class="card-body">
            <ul>
                @forelse($devices as $d)
                    <li>{{ $d->device_type }} — {{ $d->site }} (inserted {{ $d->insertion_date->format('Y-m-d') }}, {{ $d->status }})
                        @if($d->status === 'active')
                            <form action="{{ route('admin.nursing.devices.remove', $d) }}" method="post" class="d-inline">@csrf<button class="btn btn-xs btn-link">{{ __('remove') }}</button></form>
                        @endif
                    </li>
                @empty
                    <li>{{ __('None recorded.') }}</li>
                @endforelse
            </ul>
            <form action="{{ route('admin.nursing.devices.store', $episode) }}" method="post" class="row">
                @csrf
                <div class="col-md-4"><input name="device_type" class="form-control" placeholder="{{ __('Device type') }}" required></div>
                <div class="col-md-3"><input type="date" name="insertion_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                <div class="col-md-3"><input name="site" class="form-control" placeholder="{{ __('Site') }}"></div>
                <div class="col-md-2"><button class="btn btn-primary">{{ __('Add') }}</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('IV / Infusions') }}</h3></div>
        <div class="card-body">
            <ul>@forelse($infusions as $i)<li>{{ $i->fluid_name }} @ {{ $i->rate }} ({{ $i->status }})</li>@empty<li>{{ __('None recorded.') }}</li>@endforelse</ul>
            <form action="{{ route('admin.nursing.iv.store', $episode) }}" method="post" class="row">
                @csrf
                <div class="col-md-4"><input name="fluid_name" class="form-control" placeholder="{{ __('Fluid') }}" required></div>
                <div class="col-md-3"><input name="rate" class="form-control" placeholder="{{ __('Rate') }}"></div>
                <div class="col-md-3"><input type="datetime-local" name="start_time" class="form-control" required></div>
                <div class="col-md-2"><button class="btn btn-primary">{{ __('Start') }}</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Wounds / Skin') }}</h3></div>
        <div class="card-body">
            <ul>@forelse($wounds as $w)<li>{{ $w->location }} — {{ $w->wound_type }} ({{ $w->assessed_at }})</li>@empty<li>{{ __('None recorded.') }}</li>@endforelse</ul>
            <form action="{{ route('admin.nursing.wounds.store', $episode) }}" method="post" enctype="multipart/form-data" class="row">
                @csrf
                <div class="col-md-3"><input name="location" class="form-control" placeholder="{{ __('Location') }}" required></div>
                <div class="col-md-3"><input name="wound_type" class="form-control" placeholder="{{ __('Type') }}" required></div>
                <div class="col-md-4"><input type="file" name="image" class="form-control"></div>
                <div class="col-md-2"><button class="btn btn-primary">{{ __('Save') }}</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Patient Education') }}</h3></div>
        <div class="card-body">
            <ul>@forelse($education as $e)<li>{{ $e->topic }} — {{ $e->patient_understanding ?? 'understanding not recorded' }}</li>@empty<li>{{ __('None recorded.') }}</li>@endforelse</ul>
            <form action="{{ route('admin.nursing.education.store', $episode) }}" method="post" class="row">
                @csrf
                <div class="col-md-4"><input name="topic" class="form-control" placeholder="{{ __('Topic') }}" required></div>
                <div class="col-md-6"><input name="education_provided" class="form-control" placeholder="{{ __('Education provided') }}" required></div>
                <div class="col-md-2"><button class="btn btn-primary">{{ __('Save') }}</button></div>
            </form>
        </div>
    </div>
@stop
