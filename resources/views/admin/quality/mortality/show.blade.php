@extends('layouts.adminlte')

@section('page_title', $r->number)

@section('page_content')
    @include('admin.quality._flash')
    <div class="mb-3"><x-ui.status-badge :status="$r->status" /> <span class="small text-muted">{{ __('Died') }} {{ $r->died_at->setTimezone('Asia/Dhaka')->format('d M Y H:i') }} · {{ $r->patient?->enterprise_patient_no }} · {{ $r->source_type === 'ed_visit' ? __('Emergency department') : __('Ward') }}</span></div>
    @if($r->status === 'pending')
        @if($canReview)
        <form method="POST" action="{{ route('admin.quality.mortality.assign', $r->public_id) }}" class="card card-body mb-3 row g-2">@csrf<div class="col-md-8"><label class="form-label small">{{ __('Reviewer') }}</label><select name="reviewer_id" class="form-select form-select-sm">@foreach($staff as $u)<option value="{{ $u->id }}" @selected($r->reviewer_id === $u->id)>{{ $u->name }}</option>@endforeach</select></div><div class="col-md-4 d-flex align-items-end"><button class="btn btn-sm btn-outline-secondary">{{ __('Ask this person') }}</button></div></form>
        <form method="POST" action="{{ route('admin.quality.mortality.review', $r->public_id) }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('The review') }}</h3></div><div class="card-body row g-2">@csrf
            <div class="col-md-6"><label class="form-label small">{{ __('Judged to be') }}</label><select name="classification" class="form-select form-select-sm">@foreach($classes as $c)<option value="{{ $c }}">{{ __(\Illuminate\Support\Str::headline($c)) }}</option>@endforeach</select></div>
            <div class="col-12"><label class="form-label small">{{ __('Findings: what the records show about the care') }}</label><textarea name="findings" class="form-control form-control-sm" rows="5" required></textarea></div>
            <div class="col-12"><label class="form-label small">{{ __('What can be learned') }}</label><textarea name="learning" class="form-control form-control-sm" rows="2"></textarea></div>
            <div class="col-md-6"><input name="action" class="form-control form-control-sm" placeholder="{{ __('An improvement action, if one is needed') }}"></div><div class="col-md-4"><select name="owner_id" class="form-select form-select-sm"><option value="">—</option>@foreach($staff as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div><div class="col-md-2"><input type="date" name="due_on" class="form-control form-control-sm" min="{{ today()->toDateString() }}"></div>
            <div class="col-12"><button class="btn btn-sm btn-primary">{{ __('Save the review') }}</button></div></div></form>
        @else<div class="alert alert-light border small">{{ __('Waiting for a reviewer.') }}</div>@endif
    @else
        <div class="card"><dl class="row p-3 mb-0 small"><dt class="col-3">{{ __('Reviewer') }}</dt><dd class="col-9">{{ $r->reviewer?->name }} · {{ $r->reviewed_at?->setTimezone('Asia/Dhaka')->format('d M Y') }}</dd><dt class="col-3">{{ __('Judged') }}</dt><dd class="col-9">{{ __(\Illuminate\Support\Str::headline((string) $r->classification)) }}</dd>
            <dt class="col-3">{{ __('Findings') }}</dt><dd class="col-9" style="white-space: pre-line">{{ $r->findings }}</dd><dt class="col-3">{{ __('Learning') }}</dt><dd class="col-9" style="white-space: pre-line">{{ $r->learning }}</dd></dl></div>
    @endif
    @if($actions->isNotEmpty())<div class="card mt-3"><div class="card-header"><h3 class="card-title">{{ __('Actions') }}</h3></div><ul class="list-group list-group-flush small">@foreach($actions as $x)<li class="list-group-item d-flex justify-content-between">{{ $x->number }} · {{ $x->description }} · {{ $x->owner?->name }}<x-ui.status-badge :status="$x->status" /></li>@endforeach</ul></div>@endif
@stop
