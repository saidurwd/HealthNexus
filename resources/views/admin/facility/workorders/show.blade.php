@extends('layouts.adminlte')

@section('page_title', $w->number)

@section('page_content')
    @include('admin.facility._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3"><x-ui.status-badge :status="$w->status" /><span class="badge text-bg-secondary">{{ $H($w->kind) }}</span> @if($w->priority === 'urgent')<span class="badge text-bg-danger">{{ __('Urgent') }}</span>@endif
        @if($w->asset)<a href="{{ route('admin.facility.assets.show', $w->asset->public_id) }}">{{ $w->asset->tag }} · {{ $w->asset->name }}</a>@endif</div>
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3"><dl class="row p-3 mb-0 small"><dt class="col-3">{{ __('What') }}</dt><dd class="col-9">{{ $w->title }}<div style="white-space: pre-line" class="text-muted">{{ $w->description }}</div></dd><dt class="col-3">{{ __('Reported') }}</dt><dd class="col-9">{{ $w->reported_at->setTimezone('Asia/Dhaka')->format('d M Y H:i') }} {{ $w->reporter?->name }}</dd>
                <dt class="col-3">{{ __('Engineer') }}</dt><dd class="col-9">{{ $w->assignee?->name ?? '—' }}</dd>@if($w->completed_at)<dt class="col-3">{{ __('Done') }}</dt><dd class="col-9">{{ $w->completed_at->setTimezone('Asia/Dhaka')->format('d M Y H:i') }} — {{ $w->completion_note }}</dd>@endif
                @if($w->downtime_minutes !== null)<dt class="col-3">{{ __('Downtime') }}</dt><dd class="col-9">{{ intdiv($w->downtime_minutes, 60) }} {{ __('h') }} {{ $w->downtime_minutes % 60 }} {{ __('min') }}</dd>@endif<dt class="col-3">{{ __('Cost') }}</dt><dd class="col-9">{{ number_format((float) $w->cost, 2) }}</dd></dl></div>
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Parts used') }}</h3></div><ul class="list-group list-group-flush small">@forelse($w->parts as $p)<li class="list-group-item d-flex justify-content-between">{{ $p->description }} × {{ (float) $p->quantity }}<span>{{ number_format((float) $p->quantity * (float) $p->unit_cost, 2) }}</span></li>@empty<li class="list-group-item text-muted">{{ __('None.') }}</li>@endforelse</ul>
                @if($can['manage'] && in_array($w->status, ['open', 'in_progress', 'waiting_parts']))<form method="POST" action="{{ route('admin.facility.workorders.part', $w->public_id) }}" class="card-body row g-1">@csrf<div class="col-6"><input name="description" class="form-control form-control-sm" placeholder="{{ __('Part') }}" required></div><div class="col-2"><input type="number" step="0.01" min="0.01" name="quantity" class="form-control form-control-sm" value="1"></div><div class="col-2"><input type="number" step="0.01" min="0" name="unit_cost" class="form-control form-control-sm" placeholder="{{ __('Cost') }}"></div><div class="col-2"><button class="btn btn-sm btn-outline-primary w-100">{{ __('Add') }}</button></div></form>@endif</div>
        </div>
        <div class="col-lg-5">
            @if($can['manage'] && in_array($w->status, ['open', 'in_progress', 'waiting_parts']))
                <form method="POST" action="{{ route('admin.facility.workorders.assign', $w->public_id) }}" class="card card-body mb-3 row g-1">@csrf<div class="col-8"><select name="engineer_id" class="form-select form-select-sm">@foreach($staff as $u)<option value="{{ $u->id }}" @selected($w->assigned_to === $u->id)>{{ $u->name }}</option>@endforeach</select></div><div class="col-4"><button class="btn btn-sm btn-outline-secondary w-100">{{ __('Assign') }}</button></div></form>
                @if($w->status !== 'in_progress')<form method="POST" action="{{ route('admin.facility.workorders.start', $w->public_id) }}" class="mb-3">@csrf<button class="btn btn-sm btn-primary">{{ __('Start work') }}</button></form>@endif
                @if($w->status === 'in_progress')<form method="POST" action="{{ route('admin.facility.workorders.wait', $w->public_id) }}" class="card card-body mb-3 row g-1">@csrf<input name="note" class="form-control form-control-sm" placeholder="{{ __('Which part are we waiting for?') }}" required><button class="btn btn-sm btn-outline-warning">{{ __('Waiting for parts') }}</button></form>@endif
                <form method="POST" action="{{ route('admin.facility.workorders.complete', $w->public_id) }}" class="card card-body mb-3 row g-1">@csrf<textarea name="note" class="form-control form-control-sm" rows="3" placeholder="{{ __('What was done') }}" required></textarea><button class="btn btn-sm btn-success">{{ __('Finish the work') }}</button></form>
                <form method="POST" action="{{ route('admin.facility.workorders.cancel', $w->public_id) }}" class="card card-body mb-3 row g-1">@csrf<input name="reason" class="form-control form-control-sm" placeholder="{{ __('Why cancel?') }}" required><button class="btn btn-sm btn-outline-danger">{{ __('Cancel') }}</button></form>
            @endif
            @if($w->status === 'done' && $can['verify'])@if($w->assigned_to === $me)<div class="alert alert-light border small">{{ __('A second person must check the work.') }}</div>@else<form method="POST" action="{{ route('admin.facility.workorders.verify', $w->public_id) }}" class="card card-body">@csrf<div class="small mb-2">{{ __('Check that the equipment works and is safe, then confirm.') }}</div><button class="btn btn-sm btn-success">{{ __('The work is good') }}</button></form>@endif @endif
        </div>
    </div>
@stop
