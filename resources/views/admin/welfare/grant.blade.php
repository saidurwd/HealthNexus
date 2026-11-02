@extends('layouts.adminlte')

@section('page_title', $g->number)

@section('page_content')
    @include('admin.welfare._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <x-patient-banner :patient="$g->patient" />
    <div class="card mb-3"><div class="card-body small"><div class="d-flex justify-content-between"><strong>{{ $g->number }} · {{ $g->fund?->name }}</strong><x-ui.status-badge :status="$g->status" :label="$H($g->status)" /></div>
        <dl class="row mb-0 mt-2"><dt class="col-sm-4">{{ __('Most it may give') }}</dt><dd class="col-sm-8">৳ {{ number_format((float) $g->cap_amount, 2) }} · {{ __(':p% of a bill', ['p' => $g->percent]) }}</dd>
            <dt class="col-sm-4">{{ __('Given so far') }}</dt><dd class="col-sm-8">৳ {{ number_format((float) $g->spent_amount, 2) }} · {{ __('still available') }}: ৳ {{ number_format((float) $remaining, 2) }}</dd>
            <dt class="col-sm-4">{{ __('Valid') }}</dt><dd class="col-sm-8">{{ $g->valid_from?->format('d M Y') }} → {{ $g->valid_to?->format('d M Y') }}</dd>
            <dt class="col-sm-4">{{ __('What for') }}</dt><dd class="col-sm-8">{{ $g->purpose ?: '—' }}</dd>
            @if($g->decision_note)<dt class="col-sm-4">{{ __('Decision note') }}</dt><dd class="col-sm-8">{{ $g->decision_note }}</dd>@endif
            <dt class="col-sm-4">{{ __('Assessment') }}</dt><dd class="col-sm-8"><a href="{{ route('admin.welfare.assessments.show', $g->assessment->public_id) }}">{{ $g->assessment->number }}</a></dd></dl></div></div>
    @if($canApprove)<form method="POST" class="card mb-3">@csrf<div class="card-header"><h3 class="card-title">{{ __('Approve this grant') }}</h3></div><div class="card-body d-flex gap-2"><input name="note" class="form-control form-control-sm" placeholder="{{ __('Note (needed if turned down)') }}" maxlength="300">
        <button formaction="{{ route('admin.welfare.grants.approve', $g->public_id) }}" class="btn btn-sm btn-success">{{ __('Approve') }}</button><button formaction="{{ route('admin.welfare.grants.reject', $g->public_id) }}" class="btn btn-sm btn-outline-danger">{{ __('Turn down') }}</button></div></form>@elseif($g->status === 'requested')<div class="alert alert-light border small">{{ __('A second person must approve: the requester cannot approve their own grant.') }}</div>@endif
    @if($canApply)<form method="POST" action="{{ route('admin.welfare.grants.apply', $g->public_id) }}" class="card mb-3">@csrf<div class="card-header"><h3 class="card-title">{{ __('Take it off a bill') }}</h3></div><div class="card-body row g-2">
        <div class="col-md-6"><label class="form-label">{{ __('Bill') }}</label><select name="invoice_id" class="form-select form-select-sm">@forelse($invoices as $i)<option value="{{ $i->id }}">{{ $i->invoice_number }} · {{ __('to pay') }} ৳ {{ number_format((float) $i->due_amount, 2) }}</option>@empty<option value="">{{ __('No bill with something to pay') }}</option>@endforelse</select></div>
        <div class="col-md-3"><label class="form-label">{{ __('Amount (leave empty for the most allowed)') }}</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm"></div>
        <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary btn-sm" @disabled($invoices->isEmpty())>{{ __('Ask Billing') }}</button></div>
        <div class="col-12 small text-muted">{{ __('This does not change the bill. Billing\'s approver decides, and the fund is charged only then.') }}</div></div></form>@endif
    @if($g->applications->isNotEmpty())<div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Discounts asked of Billing') }}</h3></div><ul class="list-group list-group-flush small">@foreach($g->applications as $app)<li class="list-group-item d-flex justify-content-between"><span>{{ $app->invoice?->invoice_number }} · ৳ {{ number_format((float) $app->amount, 2) }}</span><x-ui.status-badge :status="$app->status" :label="$app->status === 'requested' ? __('Waiting for Billing') : $H($app->status)" /></li>@endforeach</ul></div>@endif
    @if($canCancel)<form method="POST" action="{{ route('admin.welfare.grants.cancel', $g->public_id) }}" class="card">@csrf<div class="card-body d-flex gap-2"><input name="reason" class="form-control form-control-sm" placeholder="{{ __('Why stop this grant?') }}" required maxlength="300"><button class="btn btn-sm btn-outline-secondary">{{ __('Stop the grant') }}</button></div></form>@endif
@stop
