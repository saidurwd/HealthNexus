@extends('layouts.adminlte')

@section('page_title', $c->number)

@section('page_content')
    @include('admin.vital._flash')
    <div class="mb-3"><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.vital.medicolegal') }}">{{ __('Back') }}</a></div>
    @if($late)<div class="alert alert-danger"><i class="bi bi-telephone-x"></i> {{ __('The police have not been told, and more than :n hours have passed.', ['n' => config('vital.police_inform_hours')]) }}</div>@endif
    <div class="card mb-3"><div class="card-header d-flex"><h3 class="card-title mb-0">{{ __('Police case') }} {{ $c->number }}</h3><span class="ms-auto"><x-ui.status-badge :status="$c->status" :label="__(\Illuminate\Support\Str::headline($c->status))" /></span></div>
        <div class="card-body"><dl class="row mb-0">
            <dt class="col-sm-3">{{ __('Patient') }}</dt><dd class="col-sm-9">{{ $c->patient?->full_name }} <small class="text-muted">{{ $c->patient?->enterprise_patient_no }}</small></dd>
            <dt class="col-sm-3">{{ __('Kind of case') }}</dt><dd class="col-sm-9">{{ __(\Illuminate\Support\Str::headline($c->kind)) }}</dd>
            <dt class="col-sm-3">{{ __('Brought') }}</dt><dd class="col-sm-9">{{ $c->brought_at->timezone('Asia/Dhaka')->format('d M Y, H:i') }}@if($c->brought_by) · {{ $c->brought_by }}@endif</dd>
            <dt class="col-sm-3">{{ __('What happened') }}</dt><dd class="col-sm-9" style="white-space: pre-line">{{ $c->description }}</dd>
            @if($c->injuries)<dt class="col-sm-3">{{ __('Injuries found') }}</dt><dd class="col-sm-9" style="white-space: pre-line">{{ $c->injuries }}</dd>@endif
            @if($c->police_informed_at)<dt class="col-sm-3">{{ __('Police told') }}</dt><dd class="col-sm-9">{{ $c->police_station }} · {{ __('General Diary') }} {{ $c->gd_number }}@if($c->case_number) · {{ __('Case') }} {{ $c->case_number }}@endif @if($c->officer_name) · {{ $c->officer_name }} {{ $c->officer_contact }}@endif · {{ $c->police_informed_at->timezone('Asia/Dhaka')->format('d M Y, H:i') }}</dd>@endif
            @if($c->postmortem_requested_at)<dt class="col-sm-3">{{ __('Post-mortem') }}</dt><dd class="col-sm-9">{{ $c->postmortem_done_at ? __('Done') : __('Asked for, not yet recorded') }}</dd>@endif
            @if($c->status === 'closed')<dt class="col-sm-3">{{ __('Closed because') }}</dt><dd class="col-sm-9">{{ $c->closure_note }}</dd>@endif
            @if($c->mortuary_case_id)<dt class="col-sm-3">{{ __('Mortuary') }}</dt><dd class="col-sm-9">{{ $c->status === 'closed' ? __('The body may be released.') : __('The body is held until this case is closed.') }}</dd>@endif
        </dl></div></div>
    @if($can['manage'] && $c->status !== 'closed')
    <div class="row g-3">
        @if($c->status === 'open')<div class="col-lg-8"><form method="POST" action="{{ route('admin.vital.medicolegal.inform', $c->public_id) }}" class="card h-100">@csrf<div class="card-header"><h3 class="card-title">{{ __('Record that the police were told') }}</h3></div>
            <div class="card-body row g-2"><div class="col-md-6"><label class="form-label" for="police_station">{{ __('Police station') }}</label><input id="police_station" name="police_station" class="form-control" maxlength="120" required></div>
                <div class="col-md-3"><label class="form-label" for="gd_number">{{ __('General Diary number') }}</label><input id="gd_number" name="gd_number" class="form-control" maxlength="40" required></div>
                <div class="col-md-3"><label class="form-label" for="case_number">{{ __('Case number (if any)') }}</label><input id="case_number" name="case_number" class="form-control" maxlength="40"></div>
                <div class="col-md-5"><label class="form-label" for="officer_name">{{ __('Officer') }}</label><input id="officer_name" name="officer_name" class="form-control" maxlength="150"></div>
                <div class="col-md-4"><label class="form-label" for="officer_contact">{{ __('Officer\'s phone') }}</label><input id="officer_contact" name="officer_contact" class="form-control" maxlength="60"></div>
                <div class="col-md-3"><label class="form-label" for="informed_at">{{ __('Told at (if not now)') }}</label><input id="informed_at" type="datetime-local" name="informed_at" class="form-control"></div></div>
            <div class="card-footer"><button class="btn btn-primary">{{ __('Save') }}</button></div></form></div>@endif
        @if($c->status === 'police_informed')<div class="col-md-6"><form method="POST" action="{{ route('admin.vital.medicolegal.postmortem', $c->public_id) }}" class="card h-100">@csrf<div class="card-header"><h3 class="card-title">{{ __('Post-mortem') }}</h3></div>
            <div class="card-body"><p class="small text-muted mb-0">{{ __('If the police ask for a post-mortem, record that it was asked for. The case cannot be closed until it is recorded as done.') }}</p></div><div class="card-footer"><button class="btn btn-outline-primary">{{ __('Post-mortem asked for') }}</button></div></form></div>@endif
        @if($c->status === 'postmortem_requested' && ! $c->postmortem_done_at)<div class="col-md-6"><form method="POST" action="{{ route('admin.vital.medicolegal.postmortem', $c->public_id) }}" class="card h-100">@csrf<input type="hidden" name="done" value="1"><div class="card-header"><h3 class="card-title">{{ __('Post-mortem') }}</h3></div>
            <div class="card-body"><p class="small text-muted mb-0">{{ __('Record that the post-mortem has been done.') }}</p></div><div class="card-footer"><button class="btn btn-outline-primary">{{ __('Post-mortem done') }}</button></div></form></div>@endif
        @if($c->status !== 'open')<div class="col-md-6"><form method="POST" action="{{ route('admin.vital.medicolegal.close', $c->public_id) }}" class="card h-100">@csrf<div class="card-header"><h3 class="card-title">{{ __('Close the case') }}</h3></div>
            <div class="card-body"><label class="form-label" for="closure_note">{{ __('Why it can be closed') }}</label><input id="closure_note" name="closure_note" class="form-control" maxlength="255" required></div>
            <div class="card-footer"><button class="btn btn-success">{{ __('Close') }}</button></div></form></div>@endif
    </div>
    @endif
@stop
