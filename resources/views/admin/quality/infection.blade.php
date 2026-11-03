@extends('layouts.adminlte')

@section('page_title', __('Infection control'))

@section('page_content')
    @include('admin.quality._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    @if($outbreaks->isNotEmpty())<div class="alert alert-danger"><strong>{{ __('Possible outbreak') }}</strong>
        @foreach($outbreaks as $o)<div class="d-flex flex-wrap gap-2 align-items-center small">{{ strtoupper((string) $o->hai_type) }} · {{ $o->case_count }} {{ __('cases') }} · {{ __('since') }} {{ $o->opened_on->format('d M') }} <x-ui.status-badge :status="$o->status" />
            @if($canManage)<form method="POST" action="{{ route('admin.quality.infection.outbreaks.close', $o->public_id) }}" class="d-flex gap-1 ms-auto">@csrf<input name="note" class="form-control form-control-sm" placeholder="{{ __('What was done') }}" required><label class="small text-nowrap"><input type="checkbox" name="contained" value="1"> {{ __('contained only') }}</label><button class="btn btn-sm btn-outline-light">{{ __('Close') }}</button></form>@endif</div>@endforeach</div>@endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Infections') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Onset') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Type') }}</th><th>{{ __('Organism') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead><tbody>
                @forelse($cases as $c)<tr><td>{{ $c->onset_on->format('d M') }}</td><td>{{ $c->patient?->enterprise_patient_no }}</td><td>{{ strtoupper($c->hai_type) }} @if($service->hospitalAcquired($c))<span class="badge text-bg-warning" title="{{ __('Began on hospital day :d', ['d' => $c->hospital_day]) }}">{{ __('hospital-acquired') }}</span>@endif</td><td>{{ $c->organism }}</td><td><x-ui.status-badge :status="$c->status" /></td>
                    <td>@if($canManage && $c->status === 'suspected')<form method="POST" action="{{ route('admin.quality.infection.cases.decide', $c->public_id) }}" class="d-flex gap-1">@csrf<button name="decision" value="confirm" class="btn btn-sm btn-outline-danger">{{ __('Confirm') }}</button><button name="decision" value="rule_out" class="btn btn-sm btn-outline-secondary">{{ __('Rule out') }}</button></form>@endif</td></tr>
                @empty<tr><td colspan="6" class="text-muted">{{ __('No infections recorded.') }}</td></tr>@endforelse</tbody></table></div></div>
            @if($canManage)<form method="POST" action="{{ route('admin.quality.infection.cases.store') }}" class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Record an infection') }}</h3></div><div class="card-body row g-2">@csrf
                <div class="col-md-4"><input name="patient_no" class="form-control form-control-sm" placeholder="{{ __('Patient number') }}" required></div>
                <div class="col-md-4"><select name="hai_type" class="form-select form-select-sm">@foreach($policy->list('infection.hai_types') as $t)<option value="{{ $t }}">{{ strtoupper($t) }}</option>@endforeach</select></div>
                <div class="col-md-4"><input type="date" name="onset_on" class="form-control form-control-sm" max="{{ today()->toDateString() }}" value="{{ today()->toDateString() }}" required></div>
                <div class="col-md-4"><input name="organism" class="form-control form-control-sm" placeholder="{{ __('Organism') }}"></div><div class="col-md-4"><input name="device" class="form-control form-control-sm" placeholder="{{ __('Device (line, catheter, ventilator)') }}"></div>
                <div class="col-md-4"><select name="ward_id" class="form-select form-select-sm"><option value="">{{ __('Ward') }}</option>@foreach($wards as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach</select></div>
                <div class="col-12"><button class="btn btn-sm btn-primary">{{ __('Record') }}</button></div></div></form>@endif
        </div>
        <div class="col-lg-5">
            <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Patients in isolation') }}</h3></div><ul class="list-group list-group-flush small">
                @forelse($isolations as $i)<li class="list-group-item d-flex justify-content-between align-items-center"><span><strong>{{ $H($i->precaution) }}</strong> · {{ $i->patient?->enterprise_patient_no }} <span class="text-muted">{{ $i->reason }} · {{ __('since') }} {{ $i->started_at->setTimezone('Asia/Dhaka')->format('d M') }}</span></span>
                    @if($canManage)<form method="POST" action="{{ route('admin.quality.infection.isolations.end', $i->public_id) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ __('End') }}</button></form>@endif</li>@empty<li class="list-group-item text-muted">{{ __('No one is in isolation.') }}</li>@endforelse</ul>
                @if($canManage)<form method="POST" action="{{ route('admin.quality.infection.isolations.store') }}" class="card-body row g-1">@csrf<div class="col-5"><input name="patient_no" class="form-control form-control-sm" placeholder="{{ __('Patient number') }}" required></div>
                    <div class="col-7"><select name="precaution" class="form-select form-select-sm">@foreach($policy->list('infection.precautions') as $p)<option value="{{ $p }}">{{ $H($p) }}</option>@endforeach</select></div><div class="col-9"><input name="reason" class="form-control form-control-sm" placeholder="{{ __('Why') }}" required></div><div class="col-3"><button class="btn btn-sm btn-primary w-100">{{ __('Add') }}</button></div></form>@endif</div>
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Hand hygiene') }}</h3></div><div class="card-body small">
                @if($compliance !== null)<div class="h4">{{ $compliance }}% <span class="small text-muted">{{ __('over the last 3 months; target :t%', ['t' => $policy->int('infection.hand_hygiene_target', 80)]) }}</span></div>@else<div class="text-muted">{{ __('No rounds yet.') }}</div>@endif
                <ul class="list-unstyled mb-2">@foreach($rounds->take(5) as $r)<li>{{ $r->audited_on->format('d M') }} · {{ $r->ward_name ?? '—' }} · {{ $H($r->staff_role) }}: {{ $r->moments_compliant }}/{{ $r->moments_observed }}</li>@endforeach</ul>
                @if($canManage)<form method="POST" action="{{ route('admin.quality.infection.hh.store') }}" class="row g-1">@csrf<div class="col-6"><select name="ward_id" class="form-select form-select-sm"><option value="">{{ __('Ward') }}</option>@foreach($wards as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach</select></div>
                    <div class="col-6"><select name="staff_role" class="form-select form-select-sm">@foreach(['doctor', 'nurse', 'support', 'other'] as $r)<option value="{{ $r }}">{{ $H($r) }}</option>@endforeach</select></div>
                    <div class="col-4"><input type="number" name="moments_observed" class="form-control form-control-sm" min="1" placeholder="{{ __('Seen') }}" required></div><div class="col-4"><input type="number" name="moments_compliant" class="form-control form-control-sm" min="0" placeholder="{{ __('Correct') }}" required></div><div class="col-4"><button class="btn btn-sm btn-primary w-100">{{ __('Record') }}</button></div></form>@endif</div></div>
        </div>
    </div>
@stop
