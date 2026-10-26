@extends('layouts.adminlte')

@section('page_title', __('Blood bank'))

@section('page_content')
    @include('admin.blood._flash')

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ __('Issued today') }}</div><div class="fs-3 fw-semibold">{{ $issuedToday }}</div></div></div></div>
        <div class="col-6 col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ __('On the ward, not yet started') }}</div><div class="fs-3 fw-semibold {{ $waiting ? 'text-warning' : '' }}">{{ $waiting }}</div></div></div></div>
        <div class="col-6 col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ __('Below minimum stock') }}</div><div class="fs-3 fw-semibold {{ $low ? 'text-danger' : '' }}">{{ count($low) }}</div></div></div></div>
        <div class="col-6 col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ __('Open incidents') }}</div><div class="fs-3 fw-semibold {{ $incidents->count() ? 'text-danger' : '' }}">{{ $incidents->count() }}</div></div></div></div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">{{ __('Units on the shelf') }}</h3></div>
        <div class="table-responsive"><table class="table table-sm mb-0">
            <thead><tr><th>{{ __('Group') }}</th>@foreach($components as $c)<th class="text-end">{{ __($labels[$c]) }}</th>@endforeach</tr></thead>
            <tbody>
            @forelse($summary as $group => $row)
                <tr><th>{{ __(\Illuminate\Support\Str::headline($group)) }}</th>@foreach($components as $c)<td class="text-end">{{ $row[$c] ?? 0 }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($components) + 1 }}" class="text-muted">{{ __('No units are available.') }}</td></tr>
            @endforelse
            </tbody>
        </table></div>
        @if($low)<div class="card-footer text-danger small">{{ __('Below the minimum:') }} @foreach($low as $c => $n){{ __($labels[$c]) }} ({{ $n }}) @endforeach</div>@endif
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Open requests') }}</h3></div>
                <ul class="list-group list-group-flush small">
                    @forelse($urgent as $r)
                        <li class="list-group-item d-flex justify-content-between"><span><a href="{{ route('admin.blood.requests.show', $r) }}">{{ $r->request_number }}</a> — {{ $r->patient?->full_name }} · {{ $r->units_requested }} × {{ __($labels[$r->component] ?? $r->component) }}</span>
                            <span><span class="badge text-bg-{{ in_array($r->priority, ['emergency', 'massive']) ? 'danger' : ($r->priority === 'urgent' ? 'warning' : 'secondary') }}">{{ __(\Illuminate\Support\Str::headline($r->priority)) }}</span> <x-ui.status-badge :status="$r->status" /></span></li>
                    @empty
                        <li class="list-group-item text-muted">{{ __('No open requests.') }}</li>
                    @endforelse
                </ul></div>
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Expiring soon') }}</h3></div>
                <ul class="list-group list-group-flush small">
                    @forelse($nearExpiry as $u)<li class="list-group-item"><a href="{{ route('admin.blood.units.show', $u) }}">{{ $u->unit_number }}</a> · {{ __($labels[$u->component] ?? $u->component) }} · {{ $u->abo }} {{ __(\Illuminate\Support\Str::headline($u->rh)) }} — {{ $u->expires_at->format('d M H:i') }}</li>
                    @empty<li class="list-group-item text-muted">{{ __('Nothing expires soon.') }}</li>@endforelse
                </ul></div>
        </div>
        <div class="col-lg-6">
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Storage') }}</h3></div>
                <ul class="list-group list-group-flush small">
                    @foreach($locations as $row)
                        <li class="list-group-item d-flex justify-content-between"><span>{{ $row['location']->name }} <span class="text-muted">({{ $row['location']->min_temp_c }} – {{ $row['location']->max_temp_c }} °C) · {{ $row['units'] }} {{ __('units') }}</span></span>
                            <span class="{{ $row['last']?->is_excursion ? 'text-danger fw-bold' : '' }}">{{ $row['last'] ? $row['last']->celsius.' °C · '.$row['last']->recorded_at->format('H:i') : '—' }}</span></li>
                    @endforeach
                </ul></div>
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Open incidents') }}</h3></div>
                <ul class="list-group list-group-flush small">
                    @forelse($incidents as $i)<li class="list-group-item"><strong>{{ __(\Illuminate\Support\Str::headline($i->incident_type)) }}</strong> — {{ $i->description }}</li>
                    @empty<li class="list-group-item text-muted">{{ __('No open incidents.') }}</li>@endforelse
                </ul></div>
        </div>
    </div>
@stop
