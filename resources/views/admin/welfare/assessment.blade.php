@extends('layouts.adminlte')

@section('page_title', $a->number)

@section('page_content')
    @include('admin.welfare._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <x-patient-banner :patient="$a->patient" />
    <div class="card mb-3"><div class="card-body small">
        <div class="d-flex justify-content-between"><strong>{{ $a->number }}</strong><x-ui.status-badge :status="$a->status" :label="$H($a->status)" /></div>
        <dl class="row mb-0 mt-2"><dt class="col-sm-4">{{ __('Household income per month') }}</dt><dd class="col-sm-8">৳ {{ number_format((float) $a->monthly_income, 2) }} · {{ __(':n people', ['n' => $a->household_size]) }}</dd>
            <dt class="col-sm-4">{{ __('Income per person') }}</dt><dd class="col-sm-8">৳ {{ number_format((float) $a->per_head, 2) }}</dd>
            <dt class="col-sm-4">{{ __('Work of the main earner') }}</dt><dd class="col-sm-8">{{ $a->occupation ?: '—' }}</dd>
            <dt class="col-sm-4">{{ __('Circumstances') }}</dt><dd class="col-sm-8">@forelse((array) $a->factors as $f){{ $H($f) }}@if(! $loop->last), @endif @empty — @endforelse ({{ __(':n points', ['n' => $a->score]) }})</dd>
            <dt class="col-sm-4">{{ __('Suggested by the arithmetic') }}</dt><dd class="col-sm-8">{{ $H($a->category) }}@if($a->suggested_percent) · {{ __(':p% of a bill', ['p' => $a->suggested_percent]) }}@endif</dd>
            <dt class="col-sm-4">{{ __('What was seen or checked') }}</dt><dd class="col-sm-8">{{ $a->evidence_note ?: '—' }}</dd>
            <dt class="col-sm-4">{{ __('Assessed by') }}</dt><dd class="col-sm-8">{{ $assessor ?? '—' }}</dd>
            @if($a->decided_at)<dt class="col-sm-4">{{ __('Decision') }}</dt><dd class="col-sm-8">{{ $H($a->status) }} · {{ $a->decided_at->timezone('Asia/Dhaka')->format('d M Y') }}@if($a->decision_note) · {{ $a->decision_note }}@endif @if($a->valid_until) · {{ __('valid until :d', ['d' => $a->valid_until->format('d M Y')]) }}@endif</dd>@endif</dl></div></div>
    @if($canSubmit)<form method="POST" action="{{ route('admin.welfare.assessments.submit', $a->public_id) }}" class="card mb-3">@csrf<div class="card-body d-flex gap-2"><input name="evidence_note" class="form-control form-control-sm" value="{{ $a->evidence_note }}" placeholder="{{ __('What was seen or checked') }}" maxlength="500"><button class="btn btn-sm btn-primary">{{ __('Send to the committee') }}</button></div></form>@endif
    @if($canDecide)<form method="POST" action="{{ route('admin.welfare.assessments.decide', $a->public_id) }}" class="card mb-3">@csrf<div class="card-header"><h3 class="card-title">{{ __('Committee decision') }}</h3></div><div class="card-body d-flex gap-2"><input name="note" class="form-control form-control-sm" placeholder="{{ __('Note (needed if turned down)') }}" maxlength="300"><button name="decision" value="approve" class="btn btn-sm btn-success">{{ __('Approve') }}</button><button name="decision" value="reject" class="btn btn-sm btn-outline-danger">{{ __('Turn down') }}</button></div></form>@elseif($a->status === 'submitted')<div class="alert alert-light border small">{{ __('A second person must decide: the assessor cannot approve their own assessment.') }}</div>@endif
    @if($canRequest)<form method="POST" action="{{ route('admin.welfare.grants.store', $a->public_id) }}" class="card mb-3">@csrf<div class="card-header"><h3 class="card-title">{{ __('Ask for a grant') }}</h3></div><div class="card-body row g-2">
        <div class="col-md-4"><label class="form-label">{{ __('Fund') }}</label><select name="fund_id" class="form-select form-select-sm">@foreach($funds as $f)<option value="{{ $f->id }}">{{ $f->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">{{ __('Most it may give (৳)') }}</label><input type="number" step="0.01" min="0.01" name="cap_amount" class="form-control form-control-sm" required></div>
        <div class="col-md-2"><label class="form-label">{{ __('Share of a bill (%)') }}</label><input type="number" min="1" max="100" name="percent" class="form-control form-control-sm" value="{{ $a->suggested_percent }}"></div>
        <div class="col-md-3"><label class="form-label">{{ __('What for') }}</label><input name="purpose" class="form-control form-control-sm" maxlength="200"></div>
        <div class="col-12"><button class="btn btn-primary btn-sm">{{ __('Ask for the grant') }}</button></div></div></form>@endif
    @if($a->grants->isNotEmpty())<div class="card"><div class="card-header"><h3 class="card-title">{{ __('Grants') }}</h3></div><ul class="list-group list-group-flush small">@foreach($a->grants as $g)<li class="list-group-item d-flex justify-content-between"><a href="{{ route('admin.welfare.grants.show', $g->public_id) }}">{{ $g->number }} · {{ $g->fund?->name }}</a><x-ui.status-badge :status="$g->status" :label="$H($g->status)" /></li>@endforeach</ul></div>@endif
@stop
