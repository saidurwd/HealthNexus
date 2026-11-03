@extends('layouts.adminlte')

@section('page_title', __('Transfusion'))

@section('page_content')
    <x-patient-banner :patient="$issue->patient" />
    @include('admin.blood._flash')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card"><div class="card-body"><dl class="row mb-0">
                <dt class="col-5">{{ __('Bag') }}</dt><dd class="col-7"><a href="{{ route('admin.blood.units.show', $issue->unit) }}">{{ $issue->unit->unit_number }}</a> · {{ $issue->unit->abo }} {{ __(\Illuminate\Support\Str::headline($issue->unit->rh)) }}</dd>
                <dt class="col-5">{{ __('Status') }}</dt><dd class="col-7"><x-ui.status-badge :status="$transfusion->status" /></dd>
                <dt class="col-5">{{ __('Started') }}</dt><dd class="col-7">{{ $transfusion->started_at->format('d M H:i') }}</dd>
                <dt class="col-5">{{ __('Checked by') }}</dt><dd class="col-7">{{ optional(\App\Models\User::find($transfusion->verified_by_1))->name }}, {{ optional(\App\Models\User::find($transfusion->verified_by_2))->name }}@if($transfusion->verification_override) <span class="badge text-bg-danger">{{ __('Override') }}</span>@endif</dd>
                <dt class="col-5">{{ __('Observe at (minutes)') }}</dt><dd class="col-7">{{ implode(', ', $schedule) }}</dd>
            </dl></div></div>
            @if($transfusion->status === 'in_progress')
            @can('bloodbank.transfusion.record')
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Observations') }}</h3></div>
                <form method="POST" action="{{ route('admin.blood.transfusions.observe', $transfusion) }}" data-enter-next>@csrf
                    <div class="card-body row g-2">@foreach([['temperature', 'Temperature (°C)'], ['pulse', 'Pulse Rate (bpm)'], ['respiratory_rate', 'Respiratory Rate'], ['oxygen_saturation', 'Oxygen Saturation (%)'], ['systolic', 'Systolic (mmHg)'], ['diastolic', 'Diastolic (mmHg)']] as [$f, $l])<div class="col-6 col-md-4"><label class="form-label">{{ __($l) }}</label><input type="number" step="0.1" name="{{ $f }}" class="form-control"></div>@endforeach</div>
                    <div class="card-footer"><button class="btn btn-outline-primary">{{ __('Save observations') }}</button></div></form></div>
            <form method="POST" action="{{ route('admin.blood.transfusions.complete', $transfusion) }}" class="card"><div class="card-body d-flex gap-2">@csrf<input type="number" name="volume_given_ml" class="form-control" placeholder="{{ __('Volume given (ml)') }}" required min="1" max="1000"><button class="btn btn-success">{{ __('Transfusion finished') }}</button></div></form>
            @endcan
            @endif
        </div>
        <div class="col-lg-7">
            @can('bloodbank.reaction.record')
            <div class="card border-danger"><div class="card-header"><h3 class="card-title text-danger">{{ __('Reaction — stop the transfusion') }}</h3></div>
                <form method="POST" action="{{ route('admin.blood.transfusions.reaction', $transfusion) }}" onsubmit="return confirm('{{ __('Record a reaction? The transfusion is stopped and the team is alerted.') }}')">@csrf
                    <div class="card-body row g-2">
                        <div class="col-md-6"><label class="form-label">{{ __('Type') }}</label><select name="reaction_type" class="form-select">@foreach($reactionTypes as $t)<option value="{{ $t }}">{{ __(\Illuminate\Support\Str::headline($t)) }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label">{{ __('How serious') }}</label><select name="severity" class="form-select">@foreach($severities as $s)<option value="{{ $s }}">{{ __(\Illuminate\Support\Str::headline($s)) }}</option>@endforeach</select></div>
                        <div class="col-12"><label class="form-label">{{ __('Signs and symptoms (comma separated)') }}</label><input name="symptoms" class="form-control" maxlength="500"></div>
                        <div class="col-12"><label class="form-label">{{ __('What was done') }}</label><input name="actions_taken" class="form-control" maxlength="500"></div>
                    </div>
                    <div class="card-footer"><button class="btn btn-danger">{{ __('Record reaction and stop') }}</button></div></form></div>
            @endcan
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Recorded') }}</h3></div>
                <ul class="list-group list-group-flush small">
                    @foreach($transfusion->reactions as $r)<li class="list-group-item text-danger">{{ $r->detected_at->format('H:i') }} — {{ __(\Illuminate\Support\Str::headline($r->reaction_type)) }} ({{ __(\Illuminate\Support\Str::headline($r->severity)) }})</li>@endforeach
                    @forelse($observations as $o)<li class="list-group-item">{{ $o->effective_at->format('H:i') }} — {{ $o->display }}: <strong>{{ $o->value_number }}</strong> {{ $o->unit_code }}</li>@empty<li class="list-group-item text-muted">{{ __('No observations yet.') }}</li>@endforelse
                </ul></div>
        </div>
    </div>
@stop
