@extends('layouts.adminlte')

@section('page_title', __('Refill requests'))

@section('page_content')
    @include('admin.portal._flash')
    <p class="text-muted small">{{ __('A request is only a request. Approving it tells the patient to collect from the pharmacy; the doctor still issues any new prescription in the usual way.') }}</p>
    <form method="GET" class="mb-3"><select name="status" class="form-select w-auto" onchange="this.form.submit()">@foreach(['requested', 'approved', 'declined', 'needs_visit', 'cancelled', 'all'] as $s)<option value="{{ $s }}" @selected($status === $s)>{{ __(\Illuminate\Support\Str::headline($s)) }}</option>@endforeach</select></form>
    @forelse($rows as $r)
        <div class="card"><div class="card-body"><div class="d-flex justify-content-between flex-wrap gap-2"><div><strong>{{ $r->patient->first_name }} {{ $r->patient->last_name }}</strong> <span class="text-muted">{{ $r->patient->enterprise_patient_no }}</span> · {{ __('Prescription') }} {{ $r->prescription->prescription_no }} · {{ $r->prescription->issued_at?->toDateString() }} · {{ $r->prescription->doctor?->name }}
            <div class="small">@foreach($r->prescription->items as $i){{ $i->medicine_name }} {{ $i->strength }} {{ $i->frequency }}@if(! $loop->last), @endif @endforeach</div>@if($r->note)<div class="small text-muted">“{{ $r->note }}”</div>@endif</div><x-ui.status-badge :status="$r->status" /></div>
            @can('portal.refill.review')@if($r->status === 'requested')<form method="POST" action="{{ route('admin.portal.refills.decide', $r->public_id) }}" class="mt-2 d-flex gap-2">@csrf<input name="note" class="form-control form-control-sm" placeholder="{{ __('Message to the patient (optional)') }}"><button name="decision" value="approved" class="btn btn-sm btn-outline-success">{{ __('Approve') }}</button><button name="decision" value="needs_visit" class="btn btn-sm btn-outline-warning text-nowrap">{{ __('Needs a visit') }}</button><button name="decision" value="declined" class="btn btn-sm btn-outline-danger">{{ __('Decline') }}</button></form>@endif @endcan</div></div>
    @empty<x-ui.empty-state :title="__('Nothing here.')" />@endforelse
    {{ $rows->links() }}
@stop
