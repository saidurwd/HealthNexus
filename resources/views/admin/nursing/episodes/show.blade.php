@extends('layouts.adminlte')

@section('page_title', 'Nursing — '.($episode->patient?->full_name))

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Episode</h3></div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Patient</dt><dd class="col-sm-9">{{ $episode->patient?->full_name }}</dd>
                <dt class="col-sm-3">Admission</dt><dd class="col-sm-9">{{ $episode->admission?->admission_number }}</dd>
                <dt class="col-sm-3">Location</dt><dd class="col-sm-9">{{ $episode->admission?->currentAllocation?->bed?->room?->ward?->name ?? '—' }} / {{ $episode->admission?->currentAllocation?->bed?->bed_code ?? '—' }}</dd>
                <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ ucfirst($episode->status) }}</dd>
                <dt class="col-sm-3">Assigned Nurses</dt>
                <dd class="col-sm-9">
                    @forelse($episode->assignments as $a)
                        <span class="badge bg-secondary">{{ $a->nurse?->name }} ({{ $a->assignment_type }})</span>
                        @can('nursing.assignment.update')
                            <form action="{{ route('admin.nursing.assignments.destroy', $a) }}" method="post" class="d-inline">@csrf @method('DELETE')<button class="btn btn-xs btn-link">end</button></form>
                        @endcan
                    @empty
                        —
                    @endforelse
                </dd>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Workspace</h3></div>
        <div class="card-body">
            <a class="btn btn-outline-primary" href="{{ route('admin.nursing.observations.index', $episode) }}">Vitals &amp; Observations</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.nursing.intake-output.index', $episode) }}">Intake / Output</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.nursing.mar.index', ['episode_id' => $episode->id]) }}">Medication (MAR)</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.nursing.clinical.index', $episode) }}">Devices / IV / Wounds / Education</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.nursing.notes.index', $episode) }}">Notes</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.nursing.discharge-checklist.show', $episode) }}">Discharge Checklist</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Assign Nurse</h3></div>
                <form action="{{ route('admin.nursing.assignments.store', $episode) }}" method="post">
                    @csrf
                    <div class="card-body">
                        <input type="number" name="nurse_id" class="form-control mb-2" placeholder="Nurse user ID" required>
                        <select name="assignment_type" class="form-control">
                            @foreach(['patient','bed','ward','shift','charge_nurse','team'] as $t)<option value="{{ $t }}">{{ ucfirst(str_replace('_',' ',$t)) }}</option>@endforeach
                        </select>
                    </div>
                    <div class="card-footer"><button class="btn btn-primary">Assign</button></div>
                </form>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Start Assessment</h3></div>
                <form action="{{ route('admin.nursing.assessments.store', $episode) }}" method="post">
                    @csrf
                    <div class="card-body">
                        <select name="assessment_type" class="form-control">
                            @foreach(['initial','ongoing','shift','discharge'] as $t)<option value="{{ $t }}">{{ ucfirst($t) }}</option>@endforeach
                        </select>
                    </div>
                    <div class="card-footer"><button class="btn btn-primary">Create</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Care Plans</h3></div>
                <div class="card-body">
                    @foreach($episode->carePlans as $cp)
                        <div><a href="{{ route('admin.nursing.care-plans.show', $cp) }}">Care plan #{{ $cp->id }}</a> ({{ $cp->status }})</div>
                    @endforeach
                    <form action="{{ route('admin.nursing.care-plans.store', $episode) }}" method="post" class="mt-2">@csrf<button class="btn btn-sm btn-primary">New Care Plan</button></form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Handover</h3></div>
                <div class="card-body">
                    <form action="{{ route('admin.nursing.handover.store', $episode) }}" method="post">@csrf<button class="btn btn-sm btn-primary">Prepare Handover</button></form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Escalate Concern</h3></div>
                <form action="{{ route('admin.nursing.escalations.store', $episode) }}" method="post">
                    @csrf
                    <div class="card-body">
                        <textarea name="concern" class="form-control mb-2" required placeholder="Concern"></textarea>
                        <select name="recipient_type" class="form-control mb-2">
                            @foreach(['charge_nurse','attending_physician','on_call_physician','rapid_response','icu_team'] as $t)<option value="{{ $t }}">{{ ucfirst(str_replace('_',' ',$t)) }}</option>@endforeach
                        </select>
                        <select name="severity" class="form-control">
                            @foreach(['informational','low','moderate','high','critical'] as $s)<option value="{{ $s }}">{{ ucfirst($s) }}</option>@endforeach
                        </select>
                    </div>
                    <div class="card-footer"><button class="btn btn-warning">Escalate</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Add Task</h3></div>
        <form action="{{ route('admin.nursing.tasks.store', $episode) }}" method="post">
            @csrf
            <div class="card-body row">
                <div class="col-md-4"><input name="task_type" class="form-control" placeholder="Task type" required></div>
                <div class="col-md-4"><input type="datetime-local" name="due_at" class="form-control" required></div>
                <div class="col-md-4"><button class="btn btn-primary">Create Task</button></div>
            </div>
        </form>
    </div>
@stop
