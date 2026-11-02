@extends('layouts.adminlte')

@section('page_title', __('Cleaning'))

@section('page_content')
    @include('admin.facility._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <div class="d-flex flex-wrap gap-2 mb-3">@foreach(['waiting' => 'Waiting', 'inspect' => 'To inspect', 'finished' => 'Finished'] as $k => $l)<a class="btn btn-sm {{ $show === $k ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.facility.housekeeping', ['show' => $k]) }}">{{ __($l) }}</a>@endforeach
        <a class="btn btn-sm btn-outline-secondary ms-auto" href="{{ route('admin.facility.audits') }}">{{ __('Cleaning audits') }}</a></div>
    <p class="small text-muted">{{ __('A bed that has been released and needs cleaning appears here by itself. The target is :n minutes from release to a clean bed.', ['n' => $target]) }}</p>
    @forelse($tasks as $t)
    <div class="card mb-2"><div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2 align-items-center"><strong>{{ $t->location }}</strong><span class="badge text-bg-secondary">{{ $H($t->kind) }}</span> @if($t->priority === 'urgent')<span class="badge text-bg-danger">{{ __('Urgent') }}</span>@endif <x-ui.status-badge :status="$t->status" />
            <span class="small text-muted">{{ __('waiting') }} {{ (int) $t->requested_at->diffInMinutes($t->done_at ?? now()) }} {{ __('min') }} @if($t->turnaround_minutes !== null)· {{ __('turnaround') }} {{ $t->turnaround_minutes }} {{ __('min') }}@endif</span></div>
        @if(in_array($t->status, ['pending', 'in_progress']) && $can['work'])
            <form method="POST" action="{{ route('admin.facility.housekeeping.complete', $t->public_id) }}" class="mt-2">@csrf
                @foreach($t->checklist as $i => $row)<div class="form-check"><input class="form-check-input" type="checkbox" name="done[]" value="{{ $i }}" id="c{{ $t->id }}-{{ $i }}"><label class="form-check-label small" for="c{{ $t->id }}-{{ $i }}">{{ __($row['item']) }}</label></div>@endforeach
                <div class="d-flex gap-2 mt-2"><input name="note" class="form-control form-control-sm" placeholder="{{ __('Note') }}"><button class="btn btn-sm btn-success">{{ __('Finished') }}</button></div></form>
            @if($t->status === 'pending')<form method="POST" action="{{ route('admin.facility.housekeeping.start', $t->public_id) }}" class="mt-1">@csrf<button class="btn btn-sm btn-link p-0">{{ __('Start') }}</button></form>@endif
        @elseif($t->status === 'done' && ! $t->bed_released && $can['inspect'])
            @if($t->done_by === $me)<div class="small text-muted mt-1">{{ __('A second person must inspect this clean.') }}</div>@else
            <form method="POST" action="{{ route('admin.facility.housekeeping.inspect', $t->public_id) }}" class="d-flex gap-2 mt-2">@csrf<input name="note" class="form-control form-control-sm" placeholder="{{ __('Note (needed if it must be redone)') }}"><button name="result" value="pass" class="btn btn-sm btn-success">{{ __('Pass') }}</button><button name="result" value="redo" class="btn btn-sm btn-outline-warning">{{ __('Redo') }}</button></form>@endif
        @endif
        @if(in_array($t->status, ['pending', 'in_progress']) && $can['manage'])<form method="POST" action="{{ route('admin.facility.housekeeping.cancel', $t->public_id) }}" class="d-flex gap-1 mt-1">@csrf<input name="reason" class="form-control form-control-sm" placeholder="{{ __('Why cancel?') }}" required><button class="btn btn-sm btn-outline-danger">{{ __('Cancel') }}</button></form>@endif
    </div></div>
    @empty<div class="alert alert-light border">{{ __('Nothing here.') }}</div>@endforelse
    {{ $tasks->links() }}
    @if($can['manage'] || $can['work'])<form method="POST" action="{{ route('admin.facility.housekeeping.request') }}" class="card mt-3"><div class="card-header"><h3 class="card-title">{{ __('Ask for a clean') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-4"><select name="kind" class="form-select form-select-sm">@foreach($kinds as $k)<option value="{{ $k }}">{{ $H($k) }}</option>@endforeach</select></div><div class="col-md-6"><input name="location" class="form-control form-control-sm" placeholder="{{ __('Where') }}" required></div><div class="col-md-2"><button class="btn btn-sm btn-primary w-100">{{ __('Ask') }}</button></div></div></form>@endif
@stop
