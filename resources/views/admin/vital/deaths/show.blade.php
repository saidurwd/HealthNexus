@extends('layouts.adminlte')

@section('page_title', $c->number)

@section('page_content')
    @include('admin.vital._flash')
    <style>@media print { .app-sidebar, .app-header, .app-footer, .d-print-none { display: none !important; } .app-main { margin: 0 !important; } }</style>
    <div class="d-flex flex-wrap gap-2 mb-3 d-print-none"><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.vital.deaths') }}">{{ __('Back') }}</a>
        @if(in_array($c->status, ['certified', 'issued']))<button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()"><i class="bi bi-printer"></i> {{ __('Print the certificate (A4)') }}</button>@endif
        @if($c->medicolegalCase && $can['ml'])<a class="btn btn-sm btn-outline-warning" href="{{ route('admin.vital.medicolegal.show', $c->medicolegalCase->public_id) }}"><i class="bi bi-shield-exclamation"></i> {{ __('Police case') }} {{ $c->medicolegalCase->number }}</a>@endif</div>
    <div class="card mb-3"><div class="card-header d-flex"><h3 class="card-title mb-0">{{ __('Medical certificate of cause of death') }} {{ $c->number }}</h3><span class="ms-auto"><x-ui.status-badge :status="$c->status" :label="__(\Illuminate\Support\Str::headline($c->status))" /></span></div>
        <div class="card-body"><dl class="row mb-0">
            <dt class="col-sm-3">{{ __('Patient') }}</dt><dd class="col-sm-9">{{ $c->patient?->full_name }} <small class="text-muted">{{ $c->patient?->enterprise_patient_no }}</small></dd>
            <dt class="col-sm-3">{{ __('Died') }}</dt><dd class="col-sm-9">{{ $c->died_at->timezone('Asia/Dhaka')->format('d M Y, H:i') }} · {{ __(\Illuminate\Support\Str::headline($c->place_of_death)) }}@if($c->ward_or_area) · {{ $c->ward_or_area }}@endif</dd>
            <dt class="col-sm-3">{{ __('Manner of death') }}</dt><dd class="col-sm-9">{{ __(\Illuminate\Support\Str::headline($c->manner)) }}@if($c->autopsy_done) · {{ __('post-mortem done') }}@endif</dd>
            <dt class="col-sm-3">{{ __('Part I: cause of death') }}</dt><dd class="col-sm-9"><ol type="a" class="mb-0 ps-3">@foreach(['a', 'b', 'c', 'd'] as $k)@if($c->{'cause_'.$k})<li>{{ $c->{'cause_'.$k} }}@if($c->{'interval_'.$k}) <span class="text-muted">— {{ $c->{'interval_'.$k} }}</span>@endif</li>@endif @endforeach</ol></dd>
            @if($c->contributing)<dt class="col-sm-3">{{ __('Part II') }}</dt><dd class="col-sm-9">{{ $c->contributing }}</dd>@endif
            @if($c->underlying_icd10)<dt class="col-sm-3">{{ __('Underlying cause (ICD-10)') }}</dt><dd class="col-sm-9">{{ $c->underlying_icd10 }}</dd>@endif
            @if($c->pregnancy_status)<dt class="col-sm-3">{{ __('Pregnancy') }}</dt><dd class="col-sm-9">{{ __(\Illuminate\Support\Str::headline($c->pregnancy_status)) }}</dd>@endif
            @if($c->certified_at)<dt class="col-sm-3">{{ __('Certified by') }}</dt><dd class="col-sm-9">{{ $c->certifier_name }}@if($c->certifier_reg_no) · {{ __('Registration') }} {{ $c->certifier_reg_no }}@endif · {{ $c->certified_at->timezone('Asia/Dhaka')->format('d M Y, H:i') }}</dd>@endif
            @if($c->issued_at)<dt class="col-sm-3">{{ __('Given to') }}</dt><dd class="col-sm-9">{{ $c->issued_to }} ({{ $c->issued_relation }}) · {{ \Illuminate\Support\Str::headline($c->issued_id_type) }} {{ __('checked') }} · {{ $c->issued_at->timezone('Asia/Dhaka')->format('d M Y, H:i') }}</dd>@endif
            @if($c->status === 'cancelled')<dt class="col-sm-3">{{ __('Cancelled because') }}</dt><dd class="col-sm-9">{{ $c->cancel_reason }}</dd>@endif
        </dl></div></div>
    <div class="row g-3 d-print-none">
        @if($c->status === 'draft')
            @if($can['certify'])<div class="col-md-6"><form method="POST" action="{{ route('admin.vital.deaths.certify', $c->public_id) }}" class="card h-100">@csrf<div class="card-header"><h3 class="card-title">{{ __('Certify the cause of death') }}</h3></div>
                <div class="card-body"><p class="small text-muted">{{ __('By certifying you state the cause of death as written above. After this it cannot be edited.') }}</p><label class="form-label" for="registration_no">{{ __('Your medical registration number') }}</label><input id="registration_no" name="registration_no" class="form-control" maxlength="40"></div>
                <div class="card-footer"><button class="btn btn-primary" onclick="return confirm('{{ __('Certify this cause of death?') }}')">{{ __('Certify') }}</button></div></form></div>
            @else<div class="col-12"><div class="alert alert-info small mb-0">{{ __('Waiting for a doctor to certify the cause of death.') }}</div></div>@endif
        @endif
        @if($c->status === 'certified')
            @if($c->medico_legal && $c->medicolegalCase && $c->medicolegalCase->status !== 'closed')<div class="col-12"><div class="alert alert-warning mb-0"><i class="bi bi-shield-exclamation"></i> {{ __('The police case is not closed, so this certificate cannot be given to the family yet.') }}</div></div>@endif
            @if($can['issue'])<div class="col-md-6"><form method="POST" action="{{ route('admin.vital.deaths.issue', $c->public_id) }}" class="card h-100">@csrf<div class="card-header"><h3 class="card-title">{{ __('Check the family member and issue') }}</h3></div>
                <div class="card-body row g-2"><div class="col-12"><label class="form-label" for="issued_to">{{ __('Name of the person receiving it') }}</label><input id="issued_to" name="issued_to" class="form-control" maxlength="150" required></div>
                    <div class="col-md-6"><label class="form-label" for="issued_relation">{{ __('Relation to the person who died') }}</label><input id="issued_relation" name="issued_relation" class="form-control" maxlength="60" required></div>
                    <div class="col-md-6"><label class="form-label" for="issued_id_type">{{ __('Identity paper') }}</label><select id="issued_id_type" name="issued_id_type" class="form-select">@foreach($idTypes as $t)<option value="{{ $t }}">{{ __(\Illuminate\Support\Str::headline($t)) }}</option>@endforeach</select></div>
                    <div class="col-12"><label class="form-label" for="issued_id_number">{{ __('Identity paper number') }}</label><input id="issued_id_number" name="issued_id_number" class="form-control" maxlength="40" required autocomplete="off"></div></div>
                <div class="card-footer"><button class="btn btn-primary">{{ __('Issue') }}</button></div></form></div>@endif
        @endif
        @if($c->status !== 'cancelled' && $can['cancel'])<div class="col-md-6"><form method="POST" action="{{ route('admin.vital.deaths.cancel', $c->public_id) }}" class="card h-100 border-danger">@csrf<div class="card-header"><h3 class="card-title">{{ __('Cancel this certificate') }}</h3></div>
            <div class="card-body"><p class="small text-muted">{{ __('A certificate is never edited. If something is wrong, cancel it and write a new one.') }}</p><label class="form-label" for="cancel_reason">{{ __('Reason') }}</label><input id="cancel_reason" name="cancel_reason" class="form-control" maxlength="255" required></div>
            <div class="card-footer"><button class="btn btn-outline-danger" onclick="return confirm('{{ __('Cancel this certificate?') }}')">{{ __('Cancel it') }}</button></div></form></div>@endif
    </div>
@stop
