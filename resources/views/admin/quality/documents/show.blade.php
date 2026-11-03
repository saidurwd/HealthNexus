@extends('layouts.adminlte')

@section('page_title', $doc->code.' — '.$doc->title)

@section('page_content')
    @include('admin.quality._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <div class="row g-3">
        <div class="col-lg-8">
            @if($doc->current)
            <div class="card mb-3"><div class="card-header d-flex"><h3 class="card-title">{{ __('In force') }}: v{{ $doc->current->version }} <span class="small text-muted">{{ __('from') }} {{ $doc->current->effective_on?->format('d M Y') }} · {{ __('review by') }} {{ $doc->current->review_due_on?->format('d M Y') }}</span></h3></div>
                <div class="card-body" style="white-space: pre-line">{{ $doc->current->body }}</div>
                @if($asked)<div class="card-footer">@if($acknowledged)<span class="text-success"><i class="bi bi-check-circle"></i> {{ __('You have read this version.') }}</span>@else @can('quality.document.acknowledge')<form method="POST" action="{{ route('admin.quality.documents.acknowledge', $doc->public_id) }}">@csrf<button class="btn btn-success">{{ __('I have read and understood this') }}</button></form>@endcan @endif</div>@endif
            </div>
            @else<div class="alert alert-warning">{{ __('No version has been approved yet.') }}</div>@endif

            @foreach($doc->versions->whereIn('status', ['draft', 'in_review', 'rejected']) as $v)
            <div class="card mb-3 border-info"><div class="card-header d-flex gap-2"><h3 class="card-title">v{{ $v->version }}</h3><x-ui.status-badge :status="$v->status" /><span class="small text-muted ms-auto">{{ $v->author?->name }} · {{ $v->change_note }}</span></div>
                @if(in_array($v->status, ['draft', 'rejected']) && $manage)<form method="POST" action="{{ route('admin.quality.documents.edit', $v->id) }}" class="card-body">@csrf @if($v->status === 'rejected')<div class="alert alert-warning small py-1">{{ $v->decision_note }}</div>@endif<textarea name="body" class="form-control form-control-sm mb-2" rows="10">{{ $v->body }}</textarea><input name="change_note" class="form-control form-control-sm mb-2" value="{{ $v->change_note }}" placeholder="{{ __('What changed') }}"><button class="btn btn-sm btn-outline-secondary">{{ __('Save') }}</button></form>
                    <form method="POST" action="{{ route('admin.quality.documents.submit', $v->id) }}" class="px-3 pb-3">@csrf<button class="btn btn-sm btn-primary" @disabled($v->status === 'rejected')>{{ __('Send for review') }}</button></form>
                @else<div class="card-body small" style="white-space: pre-line">{{ $v->body }}</div>@endif
                @if($v->status === 'in_review' && $approve)@if($v->author_id === $me)<div class="px-3 pb-3 small text-muted">{{ __('Someone other than the author must approve.') }}</div>@else<form method="POST" action="{{ route('admin.quality.documents.decide', $v->id) }}" class="row g-2 px-3 pb-3">@csrf<div class="col-md-4"><input type="date" name="effective_on" class="form-control form-control-sm" value="{{ today()->toDateString() }}"></div><div class="col-md-5"><input name="note" class="form-control form-control-sm" placeholder="{{ __('Note (needed to send back)') }}"></div>
                    <div class="col-md-3 d-flex gap-1"><button name="decision" value="approve" class="btn btn-sm btn-success">{{ __('Approve') }}</button><button name="decision" value="reject" class="btn btn-sm btn-outline-warning">{{ __('Send back') }}</button></div></form>@endif @endif
            </div>@endforeach

            @if($manage && $doc->status === 'active' && ! $doc->versions->whereIn('status', ['draft', 'in_review'])->count())
            <form method="POST" action="{{ route('admin.quality.documents.version', $doc->public_id) }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Write a new version') }}</h3></div><div class="card-body">@csrf<textarea name="body" class="form-control form-control-sm mb-2" rows="8" required>{{ $doc->current?->body }}</textarea><input name="change_note" class="form-control form-control-sm mb-2" placeholder="{{ __('What changed') }}" required><button class="btn btn-sm btn-primary">{{ __('Make a draft') }}</button></div></form>@endif
        </div>
        <div class="col-lg-4">
            <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Versions') }}</h3></div><ul class="list-group list-group-flush small">@foreach($doc->versions->sortByDesc('version') as $v)<li class="list-group-item d-flex justify-content-between">v{{ $v->version }} · {{ $v->change_note }}<x-ui.status-badge :status="$v->status" /></li>@endforeach</ul></div>
            @if($ack)<div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Who has read the current version') }}</h3></div><div class="card-body small">{{ $ack['acknowledged'] }} / {{ $ack['required'] }}
                <div class="progress mt-1" style="height:6px"><div class="progress-bar" style="width: {{ $ack['required'] ? round(100 * $ack['acknowledged'] / $ack['required']) : 0 }}%"></div></div></div></div>@endif
            @if($manage)
            <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Who must read it') }}</h3></div><ul class="list-group list-group-flush small">@forelse($doc->assignments as $a)<li class="list-group-item">{{ $H($a->audience) }} {{ $a->value }}</li>@empty<li class="list-group-item text-muted">{{ __('No one yet.') }}</li>@endforelse</ul>
                <form method="POST" action="{{ route('admin.quality.documents.assign', $doc->public_id) }}" class="card-body row g-1">@csrf<div class="col-5"><select name="audience" class="form-select form-select-sm" onchange="document.getElementById('aud-val').dataset.k=this.value">@foreach(['all', 'role', 'department', 'user'] as $a)<option value="{{ $a }}">{{ $H($a) }}</option>@endforeach</select></div>
                    <div class="col-7"><select name="value" id="aud-val" class="form-select form-select-sm"><option value="">—</option><optgroup label="{{ __('Role') }}">@foreach($roles as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach</optgroup><optgroup label="{{ __('Department') }}">@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</optgroup><optgroup label="{{ __('Person') }}">@foreach($staff as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</optgroup></select></div>
                    <div class="col-12"><button class="btn btn-sm btn-outline-secondary">{{ __('Add') }}</button></div></form></div>
            @if($doc->status === 'active')<form method="POST" action="{{ route('admin.quality.documents.retire', $doc->public_id) }}" class="card card-body row g-1">@csrf<input name="reason" class="form-control form-control-sm" placeholder="{{ __('Why retire it?') }}" required><button class="btn btn-sm btn-outline-danger">{{ __('Retire this document') }}</button></form>@endif
            @endif
        </div>
    </div>
@stop
