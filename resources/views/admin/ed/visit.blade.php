@extends('layouts.adminlte')

@section('page_title', __('Emergency visit').' '.$visit->visit_number)

@section('page_content')
    <x-patient-banner :patient="$visit->patient" />

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Visit') }}</h3></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">{{ __('Status') }}</dt><dd class="col-7"><x-ui.status-badge :status="$visit->status" /></dd>
                        <dt class="col-5">{{ __('Arrived') }}</dt><dd class="col-7">{{ $visit->arrival_at->format('d M H:i') }} ({{ __(\Illuminate\Support\Str::headline($visit->arrival_mode)) }})</dd>
                        <dt class="col-5">{{ __('Triage level') }}</dt><dd class="col-7">{{ $visit->triage_level ? $visit->triage_level.' — '.$levels[$visit->triage_level]['name'] : '—' }}</dd>
                        <dt class="col-5">{{ __('Main problem') }}</dt><dd class="col-7">{{ $visit->chief_complaint ?: '—' }}</dd>
                        <dt class="col-5">{{ __('Bay') }}</dt><dd class="col-7">{{ $visit->bay ? $visit->zone?->name.' · '.$visit->bay->code : '—' }}</dd>
                        <dt class="col-5">{{ __('Doctor') }}</dt><dd class="col-7">{{ $visit->doctor?->name ?? '—' }}</dd>
                        <dt class="col-5">{{ __('Nurse') }}</dt><dd class="col-7">{{ $visit->nurse?->name ?? '—' }}</dd>
                        <dt class="col-5">{{ __('Seen by a doctor') }}</dt><dd class="col-7">{{ $visit->doctor_seen_at?->format('H:i') ?? '—' }}</dd>
                    </dl>
                </div>
                @if($visit->isActive())
                <div class="card-footer d-flex flex-wrap gap-2">
                    @can('ed.visit.update')
                        <form method="POST" action="{{ route('admin.ed.visits.start', $visit) }}">@csrf<button class="btn btn-primary">{{ __('Start treatment') }}</button></form>
                        @if($visit->bay)
                            <form method="POST" action="{{ route('admin.ed.visits.bay.release', $visit) }}">@csrf @method('DELETE')<button class="btn btn-outline-secondary">{{ __('Release bay') }}</button></form>
                        @endif
                    @endcan
                </div>
                @endif
            </div>

            @can('ed.visit.update')
            @if($visit->isActive())
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Assign a bay') }}</h3></div>
                <form method="POST" action="{{ route('admin.ed.visits.bay', $visit) }}" class="card-body d-flex gap-2">
                    @csrf
                    <select name="bay" class="form-select" required>
                        <option value="">{{ __('Select Bay') }}</option>
                        @foreach($freeBays as $bay)<option value="{{ $bay->public_id }}">{{ $bay->zone->name }} · {{ $bay->code }}</option>@endforeach
                    </select>
                    <button class="btn btn-outline-primary">{{ __('Assign') }}</button>
                </form>
            </div>
            @endif
            @endcan
        </div>

        <div class="col-lg-7">
            @can('ed.triage.perform')
            @if($visit->isActive())
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ $visit->triaged_at ? __('Re-triage') : __('Triage') }}</h3></div>
                <form method="POST" action="{{ route('admin.ed.visits.triage', $visit) }}" data-enter-next>
                    @csrf
                    <div class="card-body row g-2">
                        <div class="col-12">
                            <label class="form-label">{{ __('Triage level') }}</label>
                            <div class="btn-group flex-wrap" role="group">
                                @foreach($levels as $number => $level)
                                    <input type="radio" class="btn-check" name="level" id="lvl{{ $number }}" value="{{ $number }}" required @checked($visit->triage_level === $number)>
                                    <label class="btn btn-outline-dark" for="lvl{{ $number }}"><strong>{{ $number }}</strong> {{ __($level['name']) }}</label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12"><label class="form-label">{{ __('Main problem') }}</label><input type="text" name="complaint" class="form-control" value="{{ $visit->chief_complaint }}" maxlength="500"></div>
                        @foreach([['temperature', 'Temperature (°C)'], ['pulse', 'Pulse Rate (bpm)'], ['respiratory_rate', 'Respiratory Rate'], ['oxygen_saturation', 'Oxygen Saturation (%)'], ['systolic', 'Systolic (mmHg)'], ['diastolic', 'Diastolic (mmHg)'], ['weight', 'Weight (kg)']] as [$field, $label])
                            <div class="col-6 col-md-4"><label class="form-label">{{ __($label) }}</label><input type="number" step="0.1" name="vitals[{{ $field }}]" class="form-control"></div>
                        @endforeach
                        <div class="col-12"><label class="form-label">{{ __('Notes') }}</label><input type="text" name="notes" class="form-control" maxlength="1000"></div>
                    </div>
                    <div class="card-footer"><button class="btn btn-danger">{{ __('Save triage') }}</button></div>
                </form>
            </div>
            @endif
            @endcan

            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Triage history') }}</h3></div>
                <ul class="list-group list-group-flush">
                    @forelse($visit->triageRecords->sortByDesc('assessed_at') as $record)
                        <li class="list-group-item">{{ $record->assessed_at->format('H:i') }} — <strong>{{ $record->level }}</strong> {{ $levels[$record->level]['name'] ?? '' }} @if($record->is_retriage)<span class="badge text-bg-info">{{ __('Re-triage') }}</span>@endif <small class="text-muted">{{ $record->assessor?->name }}</small></li>
                    @empty
                        <li class="list-group-item text-muted">{{ __('Not triaged yet.') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Timeline') }}</h3></div>
                <ul class="list-group list-group-flush">
                    @foreach($visit->statusHistory->sortByDesc('changed_at') as $h)
                        <li class="list-group-item small">{{ $h->changed_at->format('d M H:i') }} — {{ __(\Illuminate\Support\Str::headline($h->to_status)) }} @if($h->note)<span class="text-muted">· {{ $h->note }}</span>@endif</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@stop
