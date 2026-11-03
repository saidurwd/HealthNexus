@extends('layouts.adminlte')

@section('page_title', $p->number)

@section('page_content')
    @include('admin.mch._flash')
    <div class="mb-3"><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.mch.pregnancies') }}">{{ __('Back') }}</a></div>
    <div class="card mb-3"><div class="card-header d-flex"><h3 class="card-title mb-0">{{ __('Pregnancy') }} {{ $p->number }}</h3><span class="ms-auto"><x-ui.status-badge :status="$p->status" :label="__(\Illuminate\Support\Str::headline($p->status))" /></span></div>
        <div class="card-body"><dl class="row mb-0">
            <dt class="col-sm-3">{{ __('Woman') }}</dt><dd class="col-sm-9">{{ $p->patient?->full_name }} <small class="text-muted">{{ $p->patient?->enterprise_patient_no }}</small></dd>
            <dt class="col-sm-3">{{ __('Expected date') }}</dt><dd class="col-sm-9">{{ $p->edd->format('d M Y') }}@if($p->status === 'active') · {{ __(':n weeks now', ['n' => $weeks]) }}@endif</dd>
            <dt class="col-sm-3">{{ __('Pregnancies, births before') }}</dt><dd class="col-sm-9">{{ $p->gravida }} · {{ $p->para }}</dd>
            <dt class="col-sm-3">{{ __('Risk factors') }}</dt><dd class="col-sm-9">@forelse($p->risk_factors ?? [] as $r)<span class="badge text-bg-danger me-1">{{ __(\Illuminate\Support\Str::headline($r)) }}</span>@empty —@endforelse</dd>
            <dt class="col-sm-3">{{ __('Visits planned at (weeks)') }}</dt><dd class="col-sm-9">{{ implode(', ', $plan) }}</dd>
            @if($p->status === 'ended')<dt class="col-sm-3">{{ __('Closed because') }}</dt><dd class="col-sm-9">{{ __(\Illuminate\Support\Str::headline($p->end_reason)) }}</dd>@endif
        </dl></div></div>
    <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Antenatal visits') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>#</th><th>{{ __('Date') }}</th><th>{{ __('Weeks') }}</th><th>{{ __('BP') }}</th><th>{{ __('Weight') }}</th><th>{{ __('Hb') }}</th><th>{{ __('Baby\'s heart') }}</th><th>{{ __('Alerts') }}</th></tr></thead><tbody>
        @forelse($visits as $v)<tr><td>{{ $v->visit_no }}</td><td>{{ $v->visited_at->timezone('Asia/Dhaka')->format('d M Y') }}</td><td>{{ $v->weeks }}</td><td>{{ $v->bp_systolic ? $v->bp_systolic.'/'.$v->bp_diastolic : '—' }}</td><td>{{ $v->weight_kg ?? '—' }}</td><td>{{ $v->hemoglobin ?? '—' }}</td><td>{{ $v->fetal_heart_rate ?? '—' }}</td>
            <td>@foreach(array_merge($v->alerts ?? [], ($v->danger_signs ?? [])) as $a)<span class="badge text-bg-danger me-1">{{ __(\Illuminate\Support\Str::headline($a)) }}</span>@endforeach</td></tr>
        @empty<tr><td colspan="8"><x-ui.empty-state :title="__('No visit recorded yet.')" icon="bi-clipboard2-pulse" /></td></tr>@endforelse</tbody></table></div></div>
    @if($p->delivery)
    <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Delivery') }}</h3></div><div class="card-body"><p>{{ $p->delivery->delivered_at->timezone('Asia/Dhaka')->format('d M Y, H:i') }} · {{ __(\Illuminate\Support\Str::headline($p->delivery->mode)) }} · {{ $p->delivery->weeks }} {{ __('weeks') }}@if($p->delivery->complications) · @foreach($p->delivery->complications as $x)<span class="badge text-bg-danger">{{ __(\Illuminate\Support\Str::headline($x)) }}</span> @endforeach @endif</p>
        <ul class="mb-0">@foreach($p->delivery->newborns as $b)<li>{{ __(\Illuminate\Support\Str::headline($b->sex)) }} · {{ __(\Illuminate\Support\Str::headline($b->outcome)) }}@if($b->weight_g) · {{ $b->weight_g }} g @endif @if($b->apgar_1 !== null) · Apgar {{ $b->apgar_1 }}/{{ $b->apgar_5 }}@endif
            @if($b->child_patient_id)· <a href="{{ route('admin.mch.immunisation.card', $b->child_patient_id) }}">{{ __('Immunisation card') }}</a>@endif @if($b->birth_notification_id)· {{ __('birth notification opened') }}@endif</li>@endforeach</ul></div></div>
    @endif
    @if($p->status === 'active')
    <div class="row g-3">
        @if($can['visit'])<div class="col-lg-6"><form method="POST" action="{{ route('admin.mch.pregnancies.visit', $p->public_id) }}" class="card h-100">@csrf<div class="card-header"><h3 class="card-title">{{ __('Record an antenatal visit') }}</h3></div>
            <div class="card-body row g-2">
                <div class="col-md-4"><label class="form-label" for="v-sys">{{ __('BP top') }}</label><input id="v-sys" type="number" name="bp_systolic" class="form-control" min="60" max="260"></div>
                <div class="col-md-4"><label class="form-label" for="v-dia">{{ __('BP bottom') }}</label><input id="v-dia" type="number" name="bp_diastolic" class="form-control" min="30" max="160"></div>
                <div class="col-md-4"><label class="form-label" for="v-w">{{ __('Weight (kg)') }}</label><input id="v-w" type="number" step="0.1" name="weight_kg" class="form-control"></div>
                <div class="col-md-4"><label class="form-label" for="v-f">{{ __('Fundal height (cm)') }}</label><input id="v-f" type="number" name="fundal_height_cm" class="form-control"></div>
                <div class="col-md-4"><label class="form-label" for="v-h">{{ __('Baby\'s heart (per minute)') }}</label><input id="v-h" type="number" name="fetal_heart_rate" class="form-control"></div>
                <div class="col-md-4"><label class="form-label" for="v-hb">{{ __('Haemoglobin (g/dL)') }}</label><input id="v-hb" type="number" step="0.1" name="hemoglobin" class="form-control"></div>
                <div class="col-md-4"><label class="form-label" for="v-u">{{ __('Urine protein') }}</label><select id="v-u" name="urine_protein" class="form-select"><option value=""></option>@foreach($proteins as $x)<option value="{{ $x }}">{{ __(\Illuminate\Support\Str::headline($x)) }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label" for="v-n">{{ __('Next visit on') }}</label><input id="v-n" type="date" name="next_visit_on" class="form-control"></div>
                <div class="col-md-4 d-flex align-items-end"><div class="form-check"><input type="hidden" name="tt_given" value="0"><input class="form-check-input" type="checkbox" name="tt_given" value="1" id="v-tt"><label class="form-check-label" for="v-tt">{{ __('Tetanus dose given') }}</label></div></div>
                <div class="col-12"><div class="small fw-semibold">{{ __('Danger signs') }}</div>@foreach($signs as $x)<div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="danger_signs[]" value="{{ $x }}" id="ds-{{ $x }}"><label class="form-check-label" for="ds-{{ $x }}">{{ __(\Illuminate\Support\Str::headline($x)) }}</label></div>@endforeach</div>
                <div class="col-12"><label class="form-label" for="v-notes">{{ __('Notes') }}</label><input id="v-notes" name="notes" class="form-control" maxlength="500"></div>
            </div><div class="card-footer"><button class="btn btn-primary">{{ __('Save the visit') }}</button></div></form></div>@endif
        @if($can['deliver'])<div class="col-lg-6"><form method="POST" action="{{ route('admin.mch.pregnancies.deliver', $p->public_id) }}" class="card h-100">@csrf<div class="card-header"><h3 class="card-title">{{ __('Record the delivery') }}</h3></div>
            <div class="card-body row g-2">
                <div class="col-md-6"><label class="form-label" for="d-at">{{ __('Date and time') }}</label><input id="d-at" type="datetime-local" name="delivered_at" class="form-control"></div>
                <div class="col-md-6"><label class="form-label" for="d-mode">{{ __('Mode') }}</label><select id="d-mode" name="mode" class="form-select" required>@foreach($modes as $x)<option value="{{ $x }}">{{ __(\Illuminate\Support\Str::headline($x)) }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label" for="d-att">{{ __('Attended by') }}</label><input id="d-att" name="attendant_name" class="form-control" maxlength="150"></div>
                <div class="col-md-6"><label class="form-label" for="d-bl">{{ __('Blood loss (ml)') }}</label><input id="d-bl" type="number" name="blood_loss_ml" class="form-control"></div>
                <div class="col-12"><div class="small fw-semibold">{{ __('Complications') }}</div>@foreach($complications as $x)<div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="complications[]" value="{{ $x }}" id="dc-{{ $x }}"><label class="form-check-label" for="dc-{{ $x }}">{{ __(\Illuminate\Support\Str::headline($x)) }}</label></div>@endforeach</div>
                <div class="col-12"><div class="small fw-semibold">{{ __('Baby') }}</div></div>
                <div class="col-md-4"><label class="form-label" for="b-sex">{{ __('Sex') }}</label><select id="b-sex" name="babies[0][sex]" class="form-select" required><option value="male">{{ __('Male') }}</option><option value="female">{{ __('Female') }}</option></select></div>
                <div class="col-md-4"><label class="form-label" for="b-out">{{ __('Outcome') }}</label><select id="b-out" name="babies[0][outcome]" class="form-select">@foreach($newbornOutcomes as $x)<option value="{{ $x }}">{{ __(\Illuminate\Support\Str::headline($x)) }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label" for="b-w">{{ __('Weight (grams)') }}</label><input id="b-w" type="number" name="babies[0][weight_g]" class="form-control"></div>
                <div class="col-md-3"><label class="form-label" for="b-a1">{{ __('Apgar 1 min') }}</label><input id="b-a1" type="number" name="babies[0][apgar_1]" class="form-control" min="0" max="10"></div>
                <div class="col-md-3"><label class="form-label" for="b-a5">{{ __('Apgar 5 min') }}</label><input id="b-a5" type="number" name="babies[0][apgar_5]" class="form-control" min="0" max="10"></div>
                <div class="col-md-6 d-flex align-items-end gap-3"><div class="form-check"><input type="hidden" name="babies[0][breastfed_within_hour]" value="0"><input class="form-check-input" type="checkbox" name="babies[0][breastfed_within_hour]" value="1" id="b-bf"><label class="form-check-label" for="b-bf">{{ __('Breastfed within an hour') }}</label></div>
                    <div class="form-check"><input type="hidden" name="babies[0][vitamin_k_given]" value="0"><input class="form-check-input" type="checkbox" name="babies[0][vitamin_k_given]" value="1" id="b-vk"><label class="form-check-label" for="b-vk">{{ __('Vitamin K given') }}</label></div></div>
                <div class="col-12"><div class="small text-muted">{{ __('A live birth opens the baby\'s own record and a birth notification for a second person to check. For twins, record the first baby here and add the others from the births list.') }}</div></div>
            </div><div class="card-footer"><button class="btn btn-success">{{ __('Save the delivery') }}</button></div></form></div>@endif
        @if($can['close'])<div class="col-12"><form method="POST" action="{{ route('admin.mch.pregnancies.close', $p->public_id) }}" class="card border-secondary">@csrf<div class="card-body d-flex flex-wrap gap-2 align-items-end"><div><label class="form-label" for="end_reason">{{ __('Close without a delivery here') }}</label>
            <select id="end_reason" name="end_reason" class="form-select">@foreach(['miscarriage', 'abortion', 'ectopic', 'transferred', 'lost_to_follow_up'] as $x)<option value="{{ $x }}">{{ __(\Illuminate\Support\Str::headline($x)) }}</option>@endforeach</select></div>
            <button class="btn btn-outline-secondary" onclick="return confirm('{{ __('Close this pregnancy?') }}')">{{ __('Close the pregnancy') }}</button></div></form></div>@endif
    </div>
    @endif
@stop
