@extends('layouts.adminlte')

@section('page_title', __('Blood request').' '.$bloodRequest->request_number)

@section('page_content')
    <x-patient-banner :patient="$bloodRequest->patient" />
    @include('admin.blood._flash')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card"><div class="card-header"><h3 class="card-title">{{ $bloodRequest->units_requested }} × {{ __($component['label']) }}</h3></div>
                <div class="card-body"><dl class="row mb-0">
                    <dt class="col-5">{{ __('Status') }}</dt><dd class="col-7"><x-ui.status-badge :status="$bloodRequest->status" /></dd>
                    <dt class="col-5">{{ __('Priority') }}</dt><dd class="col-7">{{ __(\Illuminate\Support\Str::headline($bloodRequest->priority)) }}</dd>
                    <dt class="col-5">{{ __('From') }}</dt><dd class="col-7">{{ strtoupper($bloodRequest->source_module) }} · {{ $bloodRequest->requested_at->format('d M H:i') }}</dd>
                    <dt class="col-5">{{ __('Reason') }}</dt><dd class="col-7">{{ $bloodRequest->indication ?: '—' }}</dd>
                    <dt class="col-5">{{ __('Confirmed group') }}</dt><dd class="col-7">{{ $group ? $group['abo'].' '.__(\Illuminate\Support\Str::headline($group['rh'])) : __('not yet confirmed') }}</dd>
                    @if($bloodRequest->replacement_donor_offered)<dt class="col-5">{{ __('Replacement donors') }}</dt><dd class="col-7">{{ __('offered') }}</dd>@endif
                </dl></div>
                @can('bloodbank.request.cancel')@if($bloodRequest->isOpen())
                    <form method="POST" action="{{ route('admin.blood.requests.cancel', $bloodRequest) }}" class="card-footer d-flex gap-2">@csrf<input name="reason" class="form-control" placeholder="{{ __('Why cancel?') }}" required maxlength="200"><button class="btn btn-outline-secondary">{{ __('Cancel request') }}</button></form>
                @endif @endcan
            </div>
            @can('bloodbank.sample.receive')@if($bloodRequest->isOpen() || $issues->where('crossmatched', false)->isNotEmpty())
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Receive a sample') }}</h3></div>
                <form method="POST" action="{{ route('admin.blood.requests.sample', $bloodRequest) }}" data-enter-next>@csrf
                    <div class="card-body row g-2">
                        <div class="col-12"><label class="form-label">{{ __('Scan the patient wristband on the tube label') }}</label><input name="patient_scan" class="form-control" required autocomplete="off"></div>
                        <div class="col-4"><label class="form-label">{{ __('ABO') }}</label><select name="abo" class="form-select">@foreach(config('blood.abo') as $g)<option>{{ $g }}</option>@endforeach</select></div>
                        <div class="col-4"><label class="form-label">{{ __('Rh') }}</label><select name="rh" class="form-select"><option value="positive">{{ __('Positive') }}</option><option value="negative">{{ __('Negative') }}</option></select></div>
                        <div class="col-4"><label class="form-label">{{ __('Collected at') }}</label><input type="datetime-local" name="collected_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
                        <div class="col-12 d-flex gap-4"><label class="form-check"><input type="checkbox" name="labelled" value="1" class="form-check-input" checked> {{ __('Label complete and legible') }}</label><label class="form-check"><input type="checkbox" name="haemolysed" value="1" class="form-check-input"> {{ __('Haemolysed') }}</label></div>
                    </div>
                    <div class="card-footer"><button class="btn btn-outline-danger">{{ __('Receive sample') }}</button></div>
                </form></div>
            @endif @endcan
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Samples') }}</h3></div>
                <ul class="list-group list-group-flush small">@forelse($samples as $s)<li class="list-group-item">{{ $s->received_at->format('d M H:i') }} — {{ $s->abo }} {{ __(\Illuminate\Support\Str::headline($s->rh)) }} · <x-ui.status-badge :status="$s->status" /> @if($s->rejection_reason)<span class="text-danger">{{ __(\Illuminate\Support\Str::headline($s->rejection_reason)) }}</span>@endif</li>@empty<li class="list-group-item text-muted">{{ __('No sample yet.') }}</li>@endforelse</ul></div>
        </div>
        <div class="col-lg-7">
            @can('bloodbank.crossmatch.perform')@if($bloodRequest->isOpen())
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Compatible bags on the shelf (soonest expiry first)') }}</h3></div>
                <ul class="list-group list-group-flush small">
                    @forelse($candidates as $u)
                        <li class="list-group-item d-flex justify-content-between align-items-center"><span>{{ $u->unit_number }} · {{ $u->abo }} {{ __(\Illuminate\Support\Str::headline($u->rh)) }} · {{ __('expires') }} {{ $u->expires_at->format('d M') }}</span>
                            <span class="d-flex gap-1">
                                @if($group)<form method="POST" action="{{ route('admin.blood.requests.reserve', $bloodRequest) }}">@csrf<input type="hidden" name="unit" value="{{ $u->public_id }}"><button class="btn btn-sm btn-outline-primary">{{ __('Cross-match and reserve') }}</button></form>@endif
                                @can('bloodbank.issue.emergency')<form method="POST" action="{{ route('admin.blood.requests.emergency', $bloodRequest) }}" class="d-flex gap-1" onsubmit="return confirm('{{ __('Release without cross-matching?') }}')">@csrf<input type="hidden" name="unit" value="{{ $u->public_id }}"><input name="authorised_by" class="form-control form-control-sm" placeholder="{{ __('Authorising doctor') }}" required><input name="reason" class="form-control form-control-sm" placeholder="{{ __('Reason') }}" required><button class="btn btn-sm btn-danger">{{ __('Emergency release') }}</button></form>@endcan
                            </span></li>
                    @empty<li class="list-group-item text-muted">{{ $group || in_array($bloodRequest->priority, ['emergency', 'massive']) ? __('No compatible bag is available.') : __('Receive an accepted sample first.') }}</li>@endforelse
                </ul></div>
            @endif @endcan
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Reserved') }}</h3></div>
                <ul class="list-group list-group-flush small">@forelse($reservations as $r)
                    <li class="list-group-item d-flex justify-content-between align-items-center"><span>{{ $r->unit->unit_number }} · <x-ui.status-badge :status="$r->status" /> @if($r->status === 'active')<small class="text-muted">{{ __('until') }} {{ $r->reserved_until->format('d M H:i') }}</small>@endif</span>
                        @if($r->status === 'active')@can('bloodbank.issue.perform')<form method="POST" action="{{ route('admin.blood.requests.issue', $bloodRequest) }}" class="d-flex gap-1">@csrf<input type="hidden" name="unit" value="{{ $r->unit->public_id }}"><input name="issued_to" class="form-control form-control-sm" placeholder="{{ __('Collected by') }}" maxlength="100"><input type="number" step="0.1" name="issue_temp_c" class="form-control form-control-sm" style="width:90px" placeholder="°C"><button class="btn btn-sm btn-success">{{ __('Issue') }}</button></form>@endcan @endif</li>
                @empty<li class="list-group-item text-muted">{{ __('Nothing reserved.') }}</li>@endforelse</ul></div>
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Issued') }}</h3></div>
                <ul class="list-group list-group-flush small">@forelse($issues as $i)
                    <li class="list-group-item"><div class="d-flex justify-content-between"><span>{{ $i->issue_number }} · {{ $i->unit->unit_number }} · <x-ui.status-badge :status="$i->status" /> @if($i->is_emergency)<span class="badge text-bg-danger">{{ __('Emergency') }}</span>@endif</span><span>{{ $i->issued_at->format('d M H:i') }}</span></div>
                        <div class="d-flex flex-wrap gap-2 mt-1">
                            @if($i->status === 'issued')@can('bloodbank.return.accept')<form method="POST" action="{{ route('admin.blood.issues.return', $i) }}" class="d-flex gap-1">@csrf<input type="number" name="minutes_out" class="form-control form-control-sm" style="width:90px" placeholder="{{ __('min out') }}" required><input type="number" step="0.1" name="return_temp_c" class="form-control form-control-sm" style="width:80px" placeholder="°C" required><input name="reason" class="form-control form-control-sm" placeholder="{{ __('Why returned') }}" maxlength="200"><button class="btn btn-sm btn-outline-secondary">{{ __('Take back') }}</button></form>@endcan @endif
                            @if(! $i->crossmatched)@can('bloodbank.crossmatch.perform')<form method="POST" action="{{ route('admin.blood.issues.retrospective', $i) }}">@csrf<button class="btn btn-sm btn-outline-warning">{{ __('Do the owed cross-match') }}</button></form>@endcan @endif
                        </div></li>
                @empty<li class="list-group-item text-muted">{{ __('Nothing issued.') }}</li>@endforelse</ul></div>
        </div>
    </div>
@stop
