@extends('layouts.adminlte')

@section('page_title', __('Emergency visit').' '.$visit->visit_number)

@section('page_content')
    <x-patient-banner :patient="$visit->patient" />

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
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

            @if($visit->isActive())
            @can('ed.monitor.record')
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Record observations') }}</h3></div>
                <form method="POST" action="{{ route('admin.ed.visits.monitor', $visit) }}" data-enter-next>
                    @csrf
                    <div class="card-body row g-2">
                        @foreach([['temperature', 'Temperature (°C)'], ['pulse', 'Pulse Rate (bpm)'], ['respiratory_rate', 'Respiratory Rate'], ['oxygen_saturation', 'Oxygen Saturation (%)'], ['systolic', 'Systolic (mmHg)'], ['diastolic', 'Diastolic (mmHg)']] as [$field, $label])
                            <div class="col-6 col-md-4"><label class="form-label">{{ __($label) }}</label><input type="number" step="0.1" name="vitals[{{ $field }}]" class="form-control"></div>
                        @endforeach
                    </div>
                    <div class="card-footer"><button class="btn btn-outline-primary">{{ __('Save observations') }}</button></div>
                </form>
            </div>
            @endcan
            @endif

            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Resuscitation record') }}</h3></div>
                @if($visit->isActive())
                @can('ed.resus.record')
                <form method="POST" action="{{ route('admin.ed.visits.resus', $visit) }}" class="card-body d-flex flex-wrap gap-2">
                    @csrf
                    <select name="event_type" class="form-select w-auto" required>
                        @foreach(config('ed.resus_events') as $type)<option value="{{ $type }}">{{ __(\Illuminate\Support\Str::headline($type)) }}</option>@endforeach
                    </select>
                    <input type="text" name="detail" class="form-control w-auto flex-grow-1" maxlength="500" placeholder="{{ __('Details (optional)') }}">
                    <button class="btn btn-danger">{{ __('Add to record') }}</button>
                </form>
                @endcan
                @endif
                <ul class="list-group list-group-flush">
                    @forelse($visit->resusEvents as $event)
                        <li class="list-group-item small">{{ $event->occurred_at->format('H:i:s') }} — <strong>{{ __(\Illuminate\Support\Str::headline($event->event_type)) }}</strong> @if($event->detail)<span class="text-muted">· {{ $event->detail }}</span>@endif <small class="text-muted">{{ $event->recorder?->name }}</small></li>
                    @empty
                        <li class="list-group-item text-muted">{{ __('No resuscitation events recorded.') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Orders, medicines and procedures') }}</h3></div>
                @if($visit->isActive())
                <div class="card-body">
                    @can('ed.order.place')
                    <form method="POST" action="{{ route('admin.ed.visits.orders', $visit) }}" class="d-flex flex-wrap gap-2 mb-2">
                        @csrf
                        <select name="order_type" class="form-select w-auto"><option value="laboratory">{{ __('Laboratory') }}</option><option value="radiology">{{ __('Radiology') }}</option></select>
                        <input type="text" name="items[0][name]" class="form-control w-auto flex-grow-1" placeholder="{{ __('Test or scan') }}" maxlength="200" required>
                        <select name="priority" class="form-select w-auto"><option value="">{{ __('Automatic priority') }}</option><option value="routine">{{ __('Routine') }}</option><option value="urgent">{{ __('Urgent') }}</option><option value="stat">{{ __('Stat') }}</option></select>
                        <button class="btn btn-outline-primary">{{ __('Send order') }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.ed.visits.prescriptions', $visit) }}" class="d-flex flex-wrap gap-2 mb-2">
                        @csrf
                        <input type="text" name="items[0][medicine_name]" class="form-control w-auto flex-grow-1" placeholder="{{ __('Medicine') }}" maxlength="200" required>
                        <input type="text" name="items[0][strength]" class="form-control w-auto" placeholder="{{ __('Strength') }}" maxlength="100">
                        <input type="text" name="items[0][frequency]" class="form-control w-auto" placeholder="{{ __('Frequency') }}" maxlength="100" required>
                        <input type="text" name="items[0][duration]" class="form-control w-auto" placeholder="{{ __('Duration') }}" maxlength="50" required>
                        <button class="btn btn-outline-primary">{{ __('Send to pharmacy') }}</button>
                    </form>
                    @endcan
                    @can('ed.medication.administer')
                    <form method="POST" action="{{ route('admin.ed.visits.medications', $visit) }}" class="d-flex flex-wrap gap-2 mb-2 align-items-center">
                        @csrf
                        <input type="text" name="drug_name" class="form-control w-auto flex-grow-1" placeholder="{{ __('Medicine given') }}" maxlength="200" required>
                        <input type="text" name="dose" class="form-control w-auto" placeholder="{{ __('Dose') }}" maxlength="100" required>
                        <input type="text" name="route" class="form-control w-auto" placeholder="{{ __('Route') }}" maxlength="50" required>
                        <label class="form-check mb-0"><input type="checkbox" name="is_verbal_order" value="1" class="form-check-input"> {{ __('Verbal order') }}</label>
                        <select name="ordered_by" class="form-select w-auto"><option value="">{{ __('Ordered by') }}</option>@foreach($doctors as $doctor)<option value="{{ $doctor->id }}">{{ $doctor->name }}</option>@endforeach</select>
                        <button class="btn btn-outline-danger">{{ __('Record dose') }}</button>
                    </form>
                    @endcan
                    @can('ed.procedure.record')
                    <form method="POST" action="{{ route('admin.ed.visits.procedures', $visit) }}" class="d-flex flex-wrap gap-2">
                        @csrf
                        <input type="text" name="procedure_name" class="form-control w-auto flex-grow-1" placeholder="{{ __('Procedure done') }}" maxlength="200" required>
                        <input type="text" name="notes" class="form-control w-auto" placeholder="{{ __('Notes') }}" maxlength="1000">
                        <button class="btn btn-outline-secondary">{{ __('Record procedure') }}</button>
                    </form>
                    @endcan
                </div>
                @endif
                <ul class="list-group list-group-flush small">
                    @foreach($orders as $order)<li class="list-group-item">{{ $order->ordered_at?->format('H:i') }} — {{ __(\Illuminate\Support\Str::headline($order->order_type)) }}: {{ $order->items->pluck('item_name')->implode(', ') }} <span class="badge text-bg-secondary">{{ __(\Illuminate\Support\Str::headline($order->priority)) }}</span></li>@endforeach
                    @foreach($prescriptions as $rx)<li class="list-group-item">{{ $rx->prescribed_at?->format('H:i') }} — {{ __('Prescription') }}: {{ $rx->items->pluck('medicine_name')->implode(', ') }}</li>@endforeach
                    @foreach($medications as $m)<li class="list-group-item">{{ $m->given_at->format('H:i') }} — {{ __('Given') }}: <strong>{{ $m->drug_name }}</strong> {{ $m->dose }} {{ $m->route }} @if($m->is_verbal_order)<span class="badge text-bg-warning">{{ __('Verbal order') }}</span> {{ $m->orderer?->name }}@endif <small class="text-muted">{{ $m->giver?->name }}</small></li>@endforeach
                    @foreach($procedures as $p)<li class="list-group-item">{{ $p->recorded_at?->format('H:i') }} — {{ __('Procedure') }}: {{ $p->procedure_name }}</li>@endforeach
                </ul>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Protocols') }}</h3></div>
                @if($visit->isActive())
                @can('ed.protocol.run')
                <form method="POST" action="{{ route('admin.ed.visits.protocols', $visit) }}" class="card-body d-flex gap-2">
                    @csrf
                    <select name="protocol" class="form-select" required>@foreach($protocolList as $key => $protocol)<option value="{{ $key }}">{{ __($protocol['name']) }}</option>@endforeach</select>
                    <button class="btn btn-outline-danger">{{ __('Start protocol') }}</button>
                </form>
                @endcan
                @endif
                @foreach($visit->protocolRuns as $run)
                    <div class="card-body border-top">
                        <div class="d-flex justify-content-between"><strong>{{ __($protocolList[$run->protocol]['name'] ?? $run->protocol) }}</strong>
                            <span class="small text-muted">{{ $run->started_at->format('H:i') }} · {{ __(\Illuminate\Support\Str::headline($run->status)) }}</span></div>
                        <ul class="list-unstyled mb-0 mt-2">
                            @foreach($run->steps as $step)
                                <li class="d-flex align-items-center gap-2 py-1">
                                    @if($step->done_at)<i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i>
                                    @elseif($step->isOverdue())<i class="bi bi-exclamation-circle-fill text-danger" aria-hidden="true"></i>
                                    @else<i class="bi bi-circle text-muted" aria-hidden="true"></i>@endif
                                    <span class="flex-grow-1">{{ __($step->label) }} @if($step->due_at && ! $step->done_at)<small class="{{ $step->isOverdue() ? 'text-danger' : 'text-muted' }}">({{ __('due') }} {{ $step->due_at->format('H:i') }})</small>@endif @if($step->done_at)<small class="text-muted">{{ $step->done_at->format('H:i') }}</small>@endif</span>
                                    @if(! $step->done_at && $run->status === 'active')
                                        @can('ed.protocol.run')<form method="POST" action="{{ route('admin.ed.protocol-steps.done', $step) }}">@csrf<button class="btn btn-sm btn-outline-success">{{ __('Done') }}</button></form>@endcan
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @if($run->status === 'active')
                            @can('ed.protocol.run')<form method="POST" action="{{ route('admin.ed.protocol-runs.cancel', $run) }}" class="mt-2">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-muted">{{ __('Stop protocol') }}</button></form>@endcan
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Injuries (body map)') }}</h3></div>
                @if($visit->isActive())
                @can('ed.protocol.run')
                <form method="POST" action="{{ route('admin.ed.visits.injuries', $visit) }}" class="card-body d-flex flex-wrap gap-2">
                    @csrf
                    <select name="body_region" class="form-select w-auto" required>@foreach($regions as $r)<option value="{{ $r }}">{{ __($regionLabels[$r] ?? \Illuminate\Support\Str::headline($r)) }}</option>@endforeach</select>
                    <select name="injury_type" class="form-select w-auto" required>@foreach($injuryTypes as $t)<option value="{{ $t }}">{{ __(\Illuminate\Support\Str::headline($t)) }}</option>@endforeach</select>
                    <select name="severity" class="form-select w-auto" required>@foreach($severities as $sv)<option value="{{ $sv }}">{{ __(\Illuminate\Support\Str::headline($sv)) }}</option>@endforeach</select>
                    <input type="text" name="notes" class="form-control w-auto flex-grow-1" maxlength="500" placeholder="{{ __('Notes') }}">
                    <button class="btn btn-outline-secondary">{{ __('Add injury') }}</button>
                </form>
                @endcan
                @endif
                <ul class="list-group list-group-flush small">
                    @forelse($visit->injuries as $injury)
                        <li class="list-group-item"><strong>{{ __($regionLabels[$injury->body_region] ?? \Illuminate\Support\Str::headline($injury->body_region)) }}</strong> — {{ __(\Illuminate\Support\Str::headline($injury->injury_type)) }} <span class="badge text-bg-{{ in_array($injury->severity, ['severe', 'critical']) ? 'danger' : 'secondary' }}">{{ __(\Illuminate\Support\Str::headline($injury->severity)) }}</span> {{ $injury->notes }}</li>
                    @empty
                        <li class="list-group-item text-muted">{{ __('No injuries recorded.') }}</li>
                    @endforelse
                </ul>
            </div>

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
