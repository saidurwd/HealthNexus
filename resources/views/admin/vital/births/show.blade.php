@extends('layouts.adminlte')

@section('page_title', $n->number)

@section('page_content')
    @include('admin.vital._flash')
    <style>@media print { .app-sidebar, .app-header, .app-footer, .d-print-none { display: none !important; } .app-main { margin: 0 !important; } }</style>
    <div class="d-flex flex-wrap gap-2 mb-3 d-print-none"><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.vital.births') }}">{{ __('Back') }}</a>
        @if(in_array($n->status, ['issued', 'registered']))<button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()"><i class="bi bi-printer"></i> {{ __('Print the notification (A4)') }}</button>@endif</div>
    <div class="card mb-3"><div class="card-header d-flex"><h3 class="card-title mb-0">{{ __('Birth notification') }} {{ $n->number }}</h3><span class="ms-auto"><x-ui.status-badge :status="$n->status" :label="__(\Illuminate\Support\Str::headline($n->status))" /></span></div>
        <div class="card-body"><dl class="row mb-0">
            <dt class="col-sm-3">{{ __('Mother') }}</dt><dd class="col-sm-9">{{ $n->mother?->full_name }} <small class="text-muted">{{ $n->mother?->enterprise_patient_no }}</small></dd>
            <dt class="col-sm-3">{{ __('Child') }}</dt><dd class="col-sm-9">{{ $n->child_name ?: __('Not named yet') }} · {{ __(\Illuminate\Support\Str::headline($n->sex)) }} · {{ $n->outcome === 'stillbirth' ? __('Stillbirth') : __('Live birth') }}</dd>
            <dt class="col-sm-3">{{ __('Born') }}</dt><dd class="col-sm-9">{{ $n->born_at->timezone('Asia/Dhaka')->format('d M Y, H:i') }} · {{ __(\Illuminate\Support\Str::headline($n->place_of_birth)) }}@if($n->plurality > 1) · {{ __('baby :a of :b', ['a' => $n->birth_order, 'b' => $n->plurality]) }}@endif</dd>
            <dt class="col-sm-3">{{ __('Weight, weeks, delivery') }}</dt><dd class="col-sm-9">{{ $n->weight_g ? $n->weight_g.' g' : '—' }} · {{ $n->gestation_weeks ? $n->gestation_weeks.' '.__('weeks') : '—' }} · {{ $n->delivery_type ? __(\Illuminate\Support\Str::headline($n->delivery_type)) : '—' }}</dd>
            <dt class="col-sm-3">{{ __('Father') }}</dt><dd class="col-sm-9">{{ $n->father_name ?: '—' }}</dd>
            <dt class="col-sm-3">{{ __('Informant') }}</dt><dd class="col-sm-9">{{ $n->informant_name ?: '—' }}@if($n->informant_relation) ({{ $n->informant_relation }})@endif</dd>
            <dt class="col-sm-3">{{ __('Attended by') }}</dt><dd class="col-sm-9">{{ $n->attendant_name ?: '—' }}</dd>
            <dt class="col-sm-3">{{ __('Family\'s home') }}</dt><dd class="col-sm-9">{{ trim(($n->village_area ? $n->village_area.', ' : '').$place, ', ') ?: '—' }}</dd>
            @if($n->issued_at)<dt class="col-sm-3">{{ __('Given to') }}</dt><dd class="col-sm-9">{{ $n->issued_to }} · {{ $n->issued_at->timezone('Asia/Dhaka')->format('d M Y, H:i') }}</dd>@endif
            @if($n->registrar_reference)<dt class="col-sm-3">{{ __('Registrar\'s number') }}</dt><dd class="col-sm-9">{{ $n->registrar_reference }}</dd>@endif
            @if($n->status === 'cancelled')<dt class="col-sm-3">{{ __('Cancelled because') }}</dt><dd class="col-sm-9">{{ $n->cancel_reason }}</dd>@endif
        </dl>
        @if(in_array($n->status, ['issued', 'registered']))<p class="small text-muted mt-3 mb-0">{{ __('This is the hospital\'s notification of the birth. The birth certificate itself is issued by the government registrar.') }}</p>@endif</div></div>
    <div class="row g-3 d-print-none">
        @if($n->status === 'draft' && $can['issue'])<div class="col-md-6"><form method="POST" action="{{ route('admin.vital.births.issue', $n->public_id) }}" class="card h-100">@csrf<div class="card-header"><h3 class="card-title">{{ __('Check and issue') }}</h3></div>
            <div class="card-body"><p class="small text-muted">{{ __('You are the second person: check the details above against the mother\'s record, then give the notification to the family.') }}</p><label class="form-label" for="issued_to">{{ __('Given to (name)') }}</label><input id="issued_to" name="issued_to" class="form-control" maxlength="150" required></div>
            <div class="card-footer"><button class="btn btn-primary">{{ __('Issue') }}</button></div></form></div>
        @elseif($n->status === 'draft')<div class="col-12"><div class="alert alert-info small mb-0">{{ __('Waiting for a second person to check and issue it.') }}</div></div>@endif
        @if($n->status === 'issued' && $can['register'])<div class="col-md-6"><form method="POST" action="{{ route('admin.vital.births.register', $n->public_id) }}" class="card h-100">@csrf<div class="card-header"><h3 class="card-title">{{ __('Record the registrar\'s number') }}</h3></div>
            <div class="card-body"><label class="form-label" for="registrar_reference">{{ __('Birth registration number') }}</label><input id="registrar_reference" name="registrar_reference" class="form-control" maxlength="30" required></div>
            <div class="card-footer"><button class="btn btn-outline-primary">{{ __('Save') }}</button></div></form></div>@endif
        @if($n->status !== 'cancelled' && $can['cancel'])<div class="col-md-6"><form method="POST" action="{{ route('admin.vital.births.cancel', $n->public_id) }}" class="card h-100 border-danger">@csrf<div class="card-header"><h3 class="card-title">{{ __('Cancel this notification') }}</h3></div>
            <div class="card-body"><p class="small text-muted">{{ __('A notification is never edited. If a detail is wrong, cancel it and write a new one.') }}</p><label class="form-label" for="cancel_reason">{{ __('Reason') }}</label><input id="cancel_reason" name="cancel_reason" class="form-control" maxlength="255" required></div>
            <div class="card-footer"><button class="btn btn-outline-danger" onclick="return confirm('{{ __('Cancel this notification?') }}')">{{ __('Cancel it') }}</button></div></form></div>@endif
    </div>
@stop
