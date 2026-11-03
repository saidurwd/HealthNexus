@extends('layouts.adminlte')

@section('page_title', $i->number)

@section('page_content')
    @include('admin.quality._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <x-ui.status-badge :status="$i->status" />
        <span class="badge text-bg-secondary">{{ $H($i->kind) }}</span>
        <span class="badge text-bg-{{ in_array($i->harmNow(), ['major', 'catastrophic']) ? 'danger' : ($i->harmNow() === 'moderate' ? 'warning' : 'light') }}">{{ __('Harm') }}: {{ $H($i->harmNow()) }}</span>
        @if($i->due_on)<span class="small text-muted">{{ __('Due') }} {{ $i->due_on->format('d M Y') }}</span>@endif
        @if($overdue)<x-ui.status-badge status="overdue" />@endif
        @if($i->is_anonymous)<span class="badge text-bg-light border">{{ __('Reported without a name') }}</span>@endif
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('What was reported') }}</h3></div>
                <dl class="row mb-0 p-3 small">
                    <dt class="col-4">{{ __('When') }}</dt><dd class="col-8">{{ $i->occurred_at->setTimezone('Asia/Dhaka')->format('d M Y H:i') }}</dd>
                    <dt class="col-4">{{ __('Kind of event') }}</dt><dd class="col-8">{{ $H($i->effective('category')) }} @if($i->effective('category') !== $i->category)<span class="text-muted">({{ __('corrected from :c', ['c' => $H($i->category)]) }})</span>@endif</dd>
                    <dt class="col-4">{{ __('Department') }}</dt><dd class="col-8">{{ $i->department?->name ?? '—' }} @if($i->location)· {{ $i->location }}@endif</dd>
                    @if($i->patient && $manage)<dt class="col-4">{{ __('Patient') }}</dt><dd class="col-8">{{ $i->patient->enterprise_patient_no }}</dd>@endif
                    <dt class="col-4">{{ __('What happened') }}</dt><dd class="col-8" style="white-space: pre-line">{{ $i->effective('description') }}</dd>
                    @if($i->immediate_action || $i->effective('immediate_action'))<dt class="col-4">{{ __('Done straight away') }}</dt><dd class="col-8" style="white-space: pre-line">{{ $i->effective('immediate_action') }}</dd>@endif
                </dl></div>

            @if($i->amendments->isNotEmpty())<div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Corrections') }}</h3></div><ul class="list-group list-group-flush small">
                @foreach($i->amendments as $a)<li class="list-group-item"><strong>{{ $H($a->field) }}</strong>: <del class="text-muted">{{ \Illuminate\Support\Str::limit((string) $a->old_value, 80) }}</del> → {{ \Illuminate\Support\Str::limit((string) $a->new_value, 160) }}<br><span class="text-muted">{{ $a->reason }} · {{ $a->created_at->setTimezone('Asia/Dhaka')->format('d M H:i') }} @if($manage && $a->amender)· {{ $a->amender->name }}@endif</span></li>@endforeach</ul></div>@endif

            @if($i->status !== 'closed' && ($own || auth()->user()->can('quality.incident.amend')))
            <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Correct something') }}</h3></div><form method="POST" action="{{ route('admin.quality.incidents.amend', $i->public_id) }}" class="card-body row g-2">@csrf
                <div class="col-md-4"><select name="field" class="form-select form-select-sm">@foreach($service::AMENDABLE as $f)<option value="{{ $f }}">{{ $H($f) }}</option>@endforeach</select></div>
                <div class="col-md-8"><input name="value" class="form-control form-control-sm" placeholder="{{ __('The right value') }}"></div>
                <div class="col-12"><input name="reason" class="form-control form-control-sm" placeholder="{{ __('Why it is being corrected') }}" required></div>
                <div class="col-12"><button class="btn btn-sm btn-outline-secondary">{{ __('Add the correction') }}</button> <span class="small text-muted">{{ __('The original report is kept.') }}</span></div></form></div>
            @endif

            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('What has happened since') }}</h3></div><ul class="list-group list-group-flush small">
                @foreach($i->events as $e)<li class="list-group-item d-flex justify-content-between"><span>{{ $H($e->event) }} @if($e->note)<span class="text-muted">— {{ $e->note }}</span>@endif @if($manage && $e->user)<span class="text-muted">· {{ $e->user->name }}</span>@endif</span><span class="text-muted">{{ $e->created_at->setTimezone('Asia/Dhaka')->format('d M H:i') }}</span></li>@endforeach</ul></div>
        </div>

        <div class="col-lg-5">
        @if($manage)
            @if($i->status !== 'closed')
            @can('quality.incident.triage')
            <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Review') }}</h3></div><form method="POST" action="{{ route('admin.quality.incidents.triage', $i->public_id) }}" class="card-body row g-2">@csrf
                <div class="col-6"><label class="form-label small">{{ __('Harm after review') }}</label><select name="review_harm" class="form-select form-select-sm"><option value="">—</option>@foreach($policy->list('incident.harm') as $h)<option value="{{ $h }}" @selected($i->review_harm === $h)>{{ $H($h) }}</option>@endforeach</select></div>
                <div class="col-6"><label class="form-label small">{{ __('Due') }}</label><input type="date" name="due_on" class="form-control form-control-sm" value="{{ $i->due_on?->toDateString() }}"></div>
                <div class="col-12"><label class="form-label small">{{ __('Looked after by') }}</label><select name="owner_id" class="form-select form-select-sm"><option value="">—</option>@foreach($staff as $u)<option value="{{ $u->id }}" @selected($i->owner_id === $u->id)>{{ $u->name }}</option>@endforeach</select></div>
                <div class="col-12"><button class="btn btn-sm btn-primary">{{ $i->status === 'reported' ? __('Review and take it on') : __('Save') }}</button></div></form></div>
            @endcan
            @endif

            <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Investigation') }}</h3></div><div class="card-body">
                @php($inv = $i->investigation)
                @if(! $inv)
                    <p class="small text-muted mb-2">{{ $needsInvestigation ? __('This incident needs an investigation that a second person accepts before it can be closed.') : __('An investigation is optional for this level of harm.') }}</p>
                    @can('quality.investigation.manage')@if(in_array($i->status, ['triaged', 'actioned']))<form method="POST" action="{{ route('admin.quality.investigation.start', $i->public_id) }}" class="row g-2">@csrf
                        <div class="col-6"><select name="lead_id" class="form-select form-select-sm">@foreach($staff as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
                        <div class="col-6"><select name="method" class="form-select form-select-sm">@foreach(\App\Services\Quality\InvestigationService::METHODS as $m)<option value="{{ $m }}">{{ $H($m) }}</option>@endforeach</select></div>
                        <div class="col-12"><button class="btn btn-sm btn-outline-primary">{{ __('Start an investigation') }}</button></div></form>@endif @endcan
                @else
                    <div class="mb-2"><x-ui.status-badge :status="$inv->status" /> <span class="small text-muted">{{ __('Led by') }} {{ $inv->lead?->name }} · {{ $H($inv->method) }}</span></div>
                    @if(in_array($inv->status, ['open', 'returned']) && (auth()->user()->can('quality.investigation.manage') || $inv->lead_id === auth()->id()))
                        @if($inv->status === 'returned')<div class="alert alert-warning small py-1">{{ __('Sent back') }}: {{ $inv->review_note }}</div>@endif
                        <form method="POST" action="{{ route('admin.quality.investigation.save', $inv->public_id) }}">@csrf
                            <label class="form-label small">{{ __('Findings: what happened and why (the system, not a person)') }}</label><textarea name="findings" class="form-control form-control-sm mb-2" rows="5">{{ $inv->findings }}</textarea>
                            <label class="form-label small">{{ __('Causes') }}</label>
                            @foreach(array_pad($inv->root_causes ?? [], max(3, count($inv->root_causes ?? []) + 1), ['category' => '', 'text' => '']) as $n => $c)<div class="row g-1 mb-1"><div class="col-5"><select name="cause_category[{{ $n }}]" class="form-select form-select-sm">@foreach($policy->list('incident.cause_categories') as $cc)<option value="{{ $cc }}" @selected(($c['category'] ?? '') === $cc)>{{ $H($cc) }}</option>@endforeach</select></div><div class="col-7"><input name="cause_text[{{ $n }}]" class="form-control form-control-sm" value="{{ $c['text'] ?? '' }}"></div></div>@endforeach
                            <button class="btn btn-sm btn-outline-secondary mt-2">{{ __('Save') }}</button></form>
                        <form method="POST" action="{{ route('admin.quality.investigation.submit', $inv->public_id) }}" class="mt-2">@csrf<button class="btn btn-sm btn-primary">{{ __('Hand in for review') }}</button></form>
                    @else
                        <div class="small" style="white-space: pre-line">{{ $inv->findings }}</div>
                        <ul class="small mt-2 mb-0">@foreach($inv->root_causes ?? [] as $c)<li><strong>{{ $H($c['category']) }}</strong>: {{ $c['text'] }}</li>@endforeach</ul>
                        @if($inv->status === 'submitted' && auth()->user()->can('quality.investigation.review') && $inv->lead_id !== auth()->id())
                        <form method="POST" action="{{ route('admin.quality.investigation.review', $inv->public_id) }}" class="row g-2 mt-2">@csrf<div class="col-12"><input name="note" class="form-control form-control-sm" placeholder="{{ __('Note (needed if you send it back)') }}"></div>
                            <div class="col-12 d-flex gap-2"><button name="decision" value="accept" class="btn btn-sm btn-success">{{ __('Accept') }}</button><button name="decision" value="return" class="btn btn-sm btn-outline-warning">{{ __('Send back') }}</button></div></form>@endif
                    @endif
                @endif
            </div></div>

            <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Actions') }}</h3></div><ul class="list-group list-group-flush small">
                @forelse($actions as $a)<li class="list-group-item"><div class="d-flex justify-content-between"><span>{{ $a->number }} · {{ $a->description }}</span><x-ui.status-badge :status="$a->status" /></div><div class="text-muted">{{ $a->owner?->name }} · {{ __('due') }} {{ $a->due_on->format('d M Y') }}</div></li>@empty<li class="list-group-item text-muted">{{ __('No actions yet.') }}</li>@endforelse</ul>
                @can('quality.action.manage')@if($i->status !== 'closed')<form method="POST" action="{{ route('admin.quality.actions.store') }}" class="card-body row g-1">@csrf<input type="hidden" name="incident" value="{{ $i->public_id }}">
                    <div class="col-5"><select name="action_type" class="form-select form-select-sm">@foreach(['corrective', 'preventive', 'immediate', 'improvement'] as $t)<option value="{{ $t }}">{{ $H($t) }}</option>@endforeach</select></div>
                    <div class="col-7"><select name="owner_id" class="form-select form-select-sm">@foreach($staff as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
                    <div class="col-12"><input name="description" class="form-control form-control-sm" placeholder="{{ __('What is to be done') }}" required></div>
                    <div class="col-6"><input type="date" name="due_on" class="form-control form-control-sm" min="{{ today()->toDateString() }}"></div><div class="col-6"><button class="btn btn-sm btn-outline-primary w-100">{{ __('Add an action') }}</button></div></form>@endif @endcan</div>

            @if($complaints)<div class="alert alert-light border small">{{ __('Linked to patient complaint(s)') }}: {{ implode(', ', $complaints) }}</div>@endif

            @can('quality.incident.triage')
                @if($i->status === 'closed')
                    <form method="POST" action="{{ route('admin.quality.incidents.reopen', $i->public_id) }}" class="card card-body mb-3 row g-2">@csrf<div class="small">{{ __('Closed') }}: {{ $i->closure_note }}</div><input name="reason" class="form-control form-control-sm" placeholder="{{ __('Why reopen?') }}" required><button class="btn btn-sm btn-outline-warning">{{ __('Reopen') }}</button></form>
                @else
                    <form method="POST" action="{{ route('admin.quality.incidents.close', $i->public_id) }}" class="card card-body mb-3 row g-2">@csrf<textarea name="note" class="form-control form-control-sm" rows="2" placeholder="{{ __('What was found and what was done') }}" required></textarea><button class="btn btn-sm btn-success" @disabled(in_array($i->status, ['reported', 'investigating']))>{{ __('Close the incident') }}</button></form>
                @endif
            @endcan
            @can('quality.incident.reveal')
                <form method="POST" action="{{ route('admin.quality.incidents.reveal', $i->public_id) }}" class="card card-body border-warning row g-2">@csrf<div class="small text-muted">{{ __('The reporter\'s name is hidden. Showing it needs a reason, and the request is recorded.') }}</div>
                    <input name="reason" class="form-control form-control-sm" placeholder="{{ __('Why do you need to know?') }}" minlength="10" required><button class="btn btn-sm btn-outline-warning">{{ __('Show who reported this') }}</button></form>
            @endcan
        @endif
        </div>
    </div>
@stop
