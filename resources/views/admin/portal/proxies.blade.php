@extends('layouts.adminlte')

@section('page_title', __('Relatives acting for patients'))

@section('page_content')
    @include('admin.portal._flash')
    <p class="text-muted small">{{ __('A relative asks to see part of a patient\'s record. Check the evidence (parent\'s name on the birth record, guardianship paper, written consent) before you approve. For a parent and child the approval ends when the child turns 18.') }}</p>
    <form method="GET" class="mb-3"><select name="status" class="form-select w-auto" onchange="this.form.submit()">@foreach(['requested', 'active', 'rejected', 'revoked', 'expired', 'all'] as $s)<option value="{{ $s }}" @selected($status === $s)>{{ __(\Illuminate\Support\Str::headline($s)) }}</option>@endforeach</select></form>
    @forelse($rows as $p)
        <div class="card"><div class="card-body"><div class="d-flex justify-content-between flex-wrap gap-2">
            <div><strong>{{ $p->account->patient->first_name }} {{ $p->account->patient->last_name }}</strong> <span class="text-muted">({{ $p->account->patient->enterprise_patient_no }}, {{ \App\Services\Portal\PhoneNumber::mask($p->account->phone) }})</span>
                {{ __('asks to act as') }} <strong>{{ __(\Illuminate\Support\Str::headline($p->relationship)) }}</strong> {{ __('of') }} <strong>{{ $p->patient->first_name }} {{ $p->patient->last_name }}</strong> <span class="text-muted">({{ $p->patient->enterprise_patient_no }}, {{ $p->patient->date_of_birth?->toDateString() }}, {{ $p->patient->date_of_birth?->age }} {{ __('years') }})</span>
                <div class="small">{{ __('For') }}: {{ implode(', ', array_map(fn ($s) => __(\Illuminate\Support\Str::headline($s)), (array) $p->scopes)) }} @if($p->evidence) · {{ __('Evidence given') }}: {{ $p->evidence }}@endif</div>
                @if($p->decision_note)<div class="small text-muted">{{ $p->decision_note }}</div>@endif</div>
            <div><x-ui.status-badge :status="$p->status" /> @if($p->valid_to)<div class="small text-muted">{{ __('until') }} {{ $p->valid_to->toDateString() }}</div>@endif</div></div>
            @can('portal.proxy.review')@if(in_array($p->status, ['requested', 'active']))<form method="POST" action="{{ route('admin.portal.proxies.decide', $p->public_id) }}" class="mt-2 d-flex flex-wrap gap-2 align-items-center">@csrf
                <input name="note" class="form-control form-control-sm w-auto" placeholder="{{ __('Note (why, or what you checked)') }}" size="40">
                @if($p->status === 'requested')<button name="decision" value="approve" class="btn btn-sm btn-outline-success">{{ __('Approve') }}</button><button name="decision" value="reject" class="btn btn-sm btn-outline-danger">{{ __('Reject') }}</button>@endif
                @if($p->status === 'active')<button name="decision" value="revoke" class="btn btn-sm btn-outline-danger">{{ __('End access') }}</button>@endif</form>@endif @endcan</div></div>
    @empty<x-ui.empty-state :title="__('Nothing here.')" />@endforelse
    {{ $rows->links() }}
@stop
