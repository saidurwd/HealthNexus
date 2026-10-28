@extends('layouts.adminlte')

@section('page_title', __('Duty roster'))

@section('page_content')
    @include('admin.hr._flash')
    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control w-auto" onchange="this.form.submit()">
        <select name="department" class="form-select w-auto" onchange="this.form.submit()"><option value="">{{ __('All departments') }}</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected($department == $d->id)>{{ $d->name }}</option>@endforeach</select>
        <a class="btn btn-outline-secondary" href="{{ route('admin.hr.roster', ['from' => $from->subWeek()->toDateString(), 'department' => $department]) }}">←</a><a class="btn btn-outline-secondary" href="{{ route('admin.hr.roster', ['from' => $from->addWeek()->toDateString(), 'department' => $department]) }}">→</a>
    </form>
    @if($shortfalls)<div class="alert alert-warning small"><strong>{{ __('Not enough people on duty') }}:</strong> @foreach(array_slice($shortfalls, 0, 8) as $s){{ $s['date'] }} {{ $s['shift'] }} ({{ $s['rostered'] }}/{{ $s['needed'] }}); @endforeach</div>@endif
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0 align-middle">
        <thead><tr><th>{{ __('Person') }}</th>@foreach($days as $d)<th class="text-center">{{ $d->format('D d') }}</th>@endforeach</tr></thead>
        <tbody>@forelse($employees as $e)<tr><td>{{ $e->first_name }} {{ $e->last_name }}</td>
            @foreach($days as $d)@php($cell = $entries[$e->id.'|'.$d->toDateString()] ?? collect())<td class="text-center small">
                @foreach($cell as $entry)<span class="badge text-bg-{{ $entry->status === 'published' ? 'primary' : 'secondary' }}">{{ $entry->shift->code }}</span>@can('hr.roster.manage')<form method="POST" action="{{ route('admin.hr.roster.remove', $entry) }}" class="d-inline">@csrf<button class="btn btn-link btn-sm p-0 text-danger" title="{{ __('Remove') }}">×</button></form>@endcan @endforeach
                @can('hr.roster.manage')@if($cell->isEmpty())<form method="POST" action="{{ route('admin.hr.roster.assign') }}">@csrf<input type="hidden" name="employee" value="{{ $e->public_id }}"><input type="hidden" name="work_date" value="{{ $d->toDateString() }}"><select name="shift_id" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">·</option>@foreach($shifts as $s)<option value="{{ $s->id }}">{{ $s->code }}</option>@endforeach</select></form>@endif @endcan</td>@endforeach</tr>
        @empty<tr><td colspan="8" class="text-muted">{{ __('Nobody to roster.') }}</td></tr>@endforelse</tbody></table></div>
        @can('hr.roster.manage')<div class="card-footer d-flex gap-2 align-items-center"><span class="small text-muted">{{ $drafts }} {{ __('draft duty(ies) this week') }}</span><form method="POST" action="{{ route('admin.hr.roster.publish') }}" class="ms-auto">@csrf<input type="hidden" name="from" value="{{ $from->toDateString() }}"><input type="hidden" name="to" value="{{ $from->addDays(6)->toDateString() }}"><input type="hidden" name="department" value="{{ $department }}"><button class="btn btn-sm btn-success">{{ __('Publish the week') }}</button></form></div>@endcan</div>
@stop
