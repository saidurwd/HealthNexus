@extends('layouts.adminlte')

@section('page_title', __('Signal'))

@section('page_content')
    @include('admin.watch._flash')
    <div class="mb-3"><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.watch.signals') }}">{{ __('Back') }}</a></div>
    <div class="card mb-3"><div class="card-header d-flex"><h3 class="card-title mb-0">{{ app()->getLocale() === 'bn' && $s->condition?->name_bn ? $s->condition->name_bn : $s->condition?->name }}</h3><span class="ms-auto"><x-ui.status-badge :status="$s->status" :label="__(\Illuminate\Support\Str::headline($s->status))" /></span></div>
        <div class="card-body"><dl class="row mb-0">
            <dt class="col-sm-3">{{ __('Where') }}</dt><dd class="col-sm-9">{{ $district ?? __('Whole hospital') }}</dd>
            <dt class="col-sm-3">{{ __('Patients') }}</dt><dd class="col-sm-9">{{ $s->cases }} {{ __('in') }} {{ $s->window_from->format('d M') }} – {{ $s->window_to->format('d M Y') }}@if($s->baseline !== null) · {{ __('usually :n', ['n' => $s->baseline]) }}@endif</dd>
            <dt class="col-sm-3">{{ __('Why it was raised') }}</dt><dd class="col-sm-9">{{ __(['spike' => 'Rise above the usual', 'cluster' => 'Cluster in one district', 'first' => 'First cases'][$s->reason] ?? $s->reason) }}</dd>
            <dt class="col-sm-3">{{ __('Raised') }}</dt><dd class="col-sm-9">{{ $s->raised_at->timezone('Asia/Dhaka')->format('d M Y, H:i') }}</dd>
            @if($s->decision_note)<dt class="col-sm-3">{{ __('What was found') }}</dt><dd class="col-sm-9">{{ $s->decision_note }}</dd>@endif
            @if($s->reported_at)<dt class="col-sm-3">{{ __('Reported to') }}</dt><dd class="col-sm-9">{{ $s->reported_to }}@if($s->report_reference) · {{ $s->report_reference }}@endif · {{ $s->reported_at->timezone('Asia/Dhaka')->format('d M Y, H:i') }}</dd>@endif
        </dl><p class="small text-muted mt-3 mb-0">{{ __('A signal is a prompt to look, not a finding. The patients behind it are not shown here; the officer looks at the diagnoses in the clinical record.') }}</p></div></div>
    <div class="row g-3">
        @if($can['decide'] && $s->status === 'new')<div class="col-md-4"><form method="POST" action="{{ route('admin.watch.signals.investigate', $s->public_id) }}" class="card h-100">@csrf<div class="card-header"><h3 class="card-title">{{ __('Take it up') }}</h3></div><div class="card-body small text-muted">{{ __('Say that you are looking into it, so others know.') }}</div><div class="card-footer"><button class="btn btn-outline-primary">{{ __('I am looking into it') }}</button></div></form></div>@endif
        @if($can['decide'] && in_array($s->status, ['new', 'investigating']))<div class="col-md-8"><form method="POST" action="{{ route('admin.watch.signals.decide', $s->public_id) }}" class="card h-100">@csrf<div class="card-header"><h3 class="card-title">{{ __('Decide') }}</h3></div>
            <div class="card-body"><label class="form-label" for="decision_note">{{ __('What you found') }}</label><input id="decision_note" name="decision_note" class="form-control" maxlength="500" required>
                <div class="form-check mt-2"><input class="form-check-input" type="radio" name="outcome" value="confirm" id="o-c" checked><label class="form-check-label" for="o-c">{{ __('Confirmed: a real rise or cluster') }}</label></div>
                <div class="form-check"><input class="form-check-input" type="radio" name="outcome" value="dismiss" id="o-d"><label class="form-check-label" for="o-d">{{ __('Dismissed: not a concern') }}</label></div></div>
            <div class="card-footer"><button class="btn btn-primary">{{ __('Save the decision') }}</button></div></form></div>@endif
        @if($can['report'] && $s->status === 'confirmed' && ! $s->reported_at)<div class="col-md-8"><form method="POST" action="{{ route('admin.watch.signals.report', $s->public_id) }}" class="card h-100">@csrf<div class="card-header"><h3 class="card-title">{{ __('Report to the health authority') }}</h3></div>
            <div class="card-body row g-2"><div class="col-md-7"><label class="form-label" for="reported_to">{{ __('Reported to') }}</label><input id="reported_to" name="reported_to" list="authorities" class="form-control" maxlength="120" required><datalist id="authorities">@foreach($authorities as $a)<option value="{{ $a }}">@endforeach</datalist></div>
                <div class="col-md-5"><label class="form-label" for="report_reference">{{ __('Their reference') }}</label><input id="report_reference" name="report_reference" class="form-control" maxlength="60"></div></div>
            <div class="card-footer"><button class="btn btn-danger">{{ __('Record the report') }}</button></div></form></div>@endif
    </div>
@stop
