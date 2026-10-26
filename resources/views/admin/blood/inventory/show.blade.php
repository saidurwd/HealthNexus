@extends('layouts.adminlte')

@section('page_title', __('Bag').' '.$unit->unit_number)

@section('page_content')
    @include('admin.blood._flash')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card"><div class="card-body"><dl class="row mb-0">
                <dt class="col-5">{{ __('Component') }}</dt><dd class="col-7">{{ __(config("blood.components.{$unit->component}.label")) }} · {{ $unit->volume_ml }} ml</dd>
                <dt class="col-5">{{ __('Group') }}</dt><dd class="col-7">{{ $unit->abo ? $unit->abo.' '.__(\Illuminate\Support\Str::headline($unit->rh)) : '—' }}</dd>
                <dt class="col-5">{{ __('Status') }}</dt><dd class="col-7"><x-ui.status-badge :status="$unit->status" />@if($unit->blocked_reason) <small class="text-danger">{{ __(\Illuminate\Support\Str::headline($unit->blocked_reason)) }}</small>@endif</dd>
                <dt class="col-5">{{ __('Where') }}</dt><dd class="col-7">{{ $unit->location?->name ?? '—' }}</dd>
                <dt class="col-5">{{ __('Collected') }}</dt><dd class="col-7">{{ $unit->collected_at->format('d M Y H:i') }}</dd>
                <dt class="col-5">{{ __('Expires') }}</dt><dd class="col-7">{{ $unit->expires_at->format('d M Y H:i') }}</dd>
                @if($donor)<dt class="col-5">{{ __('Donor') }}</dt><dd class="col-7"><a href="{{ route('admin.blood.donors.show', $donor) }}">{{ $donor->donor_number }}</a> · <a href="{{ route('admin.blood.donations.show', $donation) }}">{{ $donation->donation_number }}</a></dd>@endif
            </dl></div>
            @can('bloodbank.unit.discard')@if(in_array($unit->status, ['quarantined', 'available', 'reserved', 'blocked', 'expired', 'issued']))
                <form method="POST" action="{{ route('admin.blood.units.discard', $unit) }}" class="card-footer d-flex gap-2" onsubmit="return confirm('{{ __('Discard this bag? This cannot be undone.') }}')">@csrf
                    <select name="reason" class="form-select w-auto">@foreach($discardReasons as $r)<option value="{{ $r }}">{{ __(\Illuminate\Support\Str::headline($r)) }}</option>@endforeach</select><input name="note" class="form-control" maxlength="300" placeholder="{{ __('Note') }}"><button class="btn btn-outline-danger">{{ __('Discard') }}</button></form>
            @endif @endcan
            </div>
            @if($recipients->isNotEmpty())
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Who received it') }}</h3></div>
                <ul class="list-group list-group-flush small">@foreach($recipients as $r)<li class="list-group-item">{{ $r['patient'] }} <span class="text-muted">{{ $r['mrn'] }}</span> — {{ __(\Illuminate\Support\Str::headline($r['state'])) }}, {{ $r['issued_at'] }}</li>@endforeach</ul></div>
            @endif
        </div>
        <div class="col-lg-7"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Every step of this bag') }}</h3></div>
            <ul class="list-group list-group-flush small">
                @foreach($events as $e)<li class="list-group-item">{{ $e->occurred_at->format('d M H:i:s') }} — <strong>{{ __(\Illuminate\Support\Str::headline($e->event_type)) }}</strong>@if($e->to_status) → {{ __(\Illuminate\Support\Str::headline($e->to_status)) }}@endif @if($e->note)<span class="text-muted">· {{ $e->note }}</span>@endif</li>@endforeach
            </ul></div></div>
    </div>
@stop
