@extends('layouts.adminlte')

@section('page_title', __('Patient requests'))

@section('page_content')
    @include('admin.portal._flash')
    <form method="GET" class="mb-3"><select name="status" class="form-select w-auto" onchange="this.form.submit()">@foreach(['open' => 'Open', 'done' => 'Done', 'rejected' => 'Rejected', 'all' => 'All'] as $k => $t)<option value="{{ $k }}" @selected($status === $k)>{{ __($t) }}</option>@endforeach</select></form>
    @forelse($rows as $r)
        <div class="card"><div class="card-body"><div class="d-flex justify-content-between flex-wrap"><div><strong>{{ __(\Illuminate\Support\Str::headline($r->kind)) }}</strong> · {{ $r->patient->first_name }} {{ $r->patient->last_name }} <span class="text-muted">{{ $r->patient->enterprise_patient_no }}</span> · {{ __('due') }} <span class="{{ $r->due_on && $r->due_on->isPast() && ! in_array($r->status, ['done', 'rejected']) ? 'text-danger' : '' }}">{{ $r->due_on?->format('d M') }}</span>
            @if($r->details)<div class="small" style="white-space: pre-wrap">{{ $r->details }}</div>@endif @if($r->resolution)<div class="small text-muted">{{ $r->resolution }}</div>@endif</div><x-ui.status-badge :status="$r->status" /></div>
            @can('portal.request.manage')@if(! in_array($r->status, ['done', 'rejected']))<form method="POST" action="{{ route('admin.portal.requests.update', $r->public_id) }}" class="mt-2 d-flex gap-2">@csrf<input name="resolution" class="form-control form-control-sm" placeholder="{{ __('What was done (the patient is told)') }}"><button name="status" value="in_progress" class="btn btn-sm btn-outline-primary text-nowrap">{{ __('Working on it') }}</button><button name="status" value="done" class="btn btn-sm btn-outline-success">{{ __('Done') }}</button><button name="status" value="rejected" class="btn btn-sm btn-outline-danger">{{ __('Refuse') }}</button></form>@endif @endcan</div></div>
    @empty<x-ui.empty-state :title="__('Nothing here.')" />@endforelse
    {{ $rows->links() }}
@stop
