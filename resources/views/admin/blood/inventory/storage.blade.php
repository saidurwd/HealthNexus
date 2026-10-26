@extends('layouts.adminlte')

@section('page_title', __('Storage and temperature'))

@section('page_content')
    @include('admin.blood._flash')
    <div class="row g-3">
        @foreach($locations as $row)
            @php($l = $row['location'])
            <div class="col-md-6"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ $l->name }} <small class="text-muted">{{ $l->min_temp_c }} – {{ $l->max_temp_c }} °C · {{ $row['units'] }} {{ __('units') }}</small></h3></div>
                @can('bloodbank.temperature.record')
                <form method="POST" action="{{ route('admin.blood.inventory.temperature', $l) }}" class="card-body d-flex gap-2">@csrf<input type="number" step="0.1" name="celsius" class="form-control" placeholder="{{ __('Reading (°C)') }}" required><button class="btn btn-outline-primary">{{ __('Record') }}</button></form>
                @endcan
                <ul class="list-group list-group-flush small">@forelse($row['logs'] as $log)<li class="list-group-item d-flex justify-content-between"><span>{{ $log->recorded_at->format('d M H:i') }} · {{ __(\Illuminate\Support\Str::headline($log->source)) }}</span><strong class="{{ $log->is_excursion ? 'text-danger' : '' }}">{{ $log->celsius }} °C</strong></li>@empty<li class="list-group-item text-muted">{{ __('No readings yet.') }}</li>@endforelse</ul></div></div>
        @endforeach
    </div>
@stop
