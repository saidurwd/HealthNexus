@extends('layouts.adminlte')

@section('page_title', $a->number.' — '.$a->title)

@section('page_content')
    @include('admin.quality._flash')
    <div class="mb-3"><x-ui.status-badge :status="$a->status" /> <span class="small text-muted">{{ $a->audited_on->format('d M Y') }} · {{ __('sample') }} {{ $a->sample_size }} · {{ $a->auditor?->name }}</span> @if($a->compliance !== null)<span class="badge text-bg-primary">{{ (float) $a->compliance }}% {{ __('met') }}</span>@endif</div>
    @if($a->standard)<p class="small">{{ __('Standard') }}: {{ $a->standard }}</p>@endif
    @if($a->status !== 'completed' && $canManage)
    <form method="POST" action="{{ route('admin.quality.audits.record', $a->public_id) }}" class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('How often was each criterion met?') }}</h3></div><div class="card-body">@csrf
        @foreach(array_pad($a->findings->all(), max(5, $a->findings->count() + 1), null) as $n => $f)<div class="row g-1 mb-1"><div class="col-md-7"><input name="criterion[{{ $n }}]" class="form-control form-control-sm" placeholder="{{ __('Criterion') }}" value="{{ $f->criterion ?? '' }}"></div>
            <div class="col-md-1"><input type="number" min="0" name="met[{{ $n }}]" class="form-control form-control-sm" placeholder="{{ __('Met') }}" value="{{ $f->met ?? '' }}"></div><div class="col-md-1"><input type="number" min="0" name="not_met[{{ $n }}]" class="form-control form-control-sm" placeholder="{{ __('Not met') }}" value="{{ $f->not_met ?? '' }}"></div>
            <div class="col-md-3"><input name="comment[{{ $n }}]" class="form-control form-control-sm" placeholder="{{ __('Comment') }}" value="{{ $f->comment ?? '' }}"></div></div>@endforeach
        <button class="btn btn-sm btn-outline-secondary mt-2">{{ __('Save') }}</button></div></form>
    @else
    <div class="card mb-3"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Criterion') }}</th><th class="text-end">{{ __('Met') }}</th><th class="text-end">{{ __('Not met') }}</th><th class="text-end">%</th></tr></thead><tbody>
        @foreach($a->findings as $f)@php($t = $f->met + $f->not_met)<tr><td>{{ $f->criterion }} @if($f->comment)<div class="small text-muted">{{ $f->comment }}</div>@endif</td><td class="text-end">{{ $f->met }}</td><td class="text-end">{{ $f->not_met }}</td><td class="text-end">{{ $t ? round(100 * $f->met / $t, 1) : '—' }}</td></tr>@endforeach</tbody></table></div></div>
    @endif
    @if($a->status !== 'completed' && $canManage && $a->findings->isNotEmpty())
    <form method="POST" action="{{ route('admin.quality.audits.complete', $a->public_id) }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Finish the audit') }}</h3></div><div class="card-body">@csrf
        <textarea name="summary" class="form-control form-control-sm mb-2" rows="3" placeholder="{{ __('Summary: what the audit found') }}"></textarea>
        @php($pre = $a->findings->filter(fn ($f) => ($f->met + $f->not_met) > 0 && 100 * $f->met / ($f->met + $f->not_met) < (float) config('quality.audit.gap_threshold', 90)))
        @if($pre->isNotEmpty())<div class="small mb-1">{{ __('Criteria below the standard — give each an owner to raise an action:') }}</div>
            @foreach($pre->values() as $n => $f)<div class="row g-1 mb-1"><div class="col-md-6"><input type="hidden" name="gap_criterion[{{ $n }}]" value="{{ $f->criterion }}"><div class="form-control-plaintext form-control-sm">{{ $f->criterion }}</div></div>
                <div class="col-md-4"><select name="gap_owner[{{ $n }}]" class="form-select form-select-sm"><option value="">—</option>@foreach($staff as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div><div class="col-md-2"><input type="date" name="gap_due[{{ $n }}]" class="form-control form-control-sm" min="{{ today()->toDateString() }}"></div></div>@endforeach @endif
        <button class="btn btn-sm btn-success mt-2">{{ __('Complete the audit') }}</button></div></form>
    @elseif($a->summary)<div class="card"><div class="card-body small" style="white-space: pre-line">{{ $a->summary }}</div></div>@endif
    @if($raised->isNotEmpty())<div class="card mt-3"><div class="card-header"><h3 class="card-title">{{ __('Actions raised') }}</h3></div><ul class="list-group list-group-flush small">@foreach($raised as $x)<li class="list-group-item d-flex justify-content-between">{{ $x->number }} · {{ $x->description }} · {{ $x->owner?->name }}<x-ui.status-badge :status="$x->status" /></li>@endforeach</ul></div>@endif
@stop
