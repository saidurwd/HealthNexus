@extends('layouts.adminlte')

@section('page_title', 'Surgery '.$surgery->surgery_number)

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ $surgery->surgery_number }} — {{ $surgery->patient?->full_name }} <span class="badge bg-info">{{ ucwords(str_replace('_',' ',$surgery->status)) }}</span></h3></div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">{{ __('Procedure') }}</dt><dd class="col-sm-9">{{ $surgery->primaryProcedure?->name ?? '—' }}</dd>
                <dt class="col-sm-3">{{ __('Site / Laterality') }}</dt><dd class="col-sm-9">{{ $surgery->site ?? '—' }} / {{ $surgery->laterality ?? '—' }}</dd>
                <dt class="col-sm-3">{{ __('Theatre') }}</dt><dd class="col-sm-9">{{ $surgery->theatre?->name ?? 'Not scheduled' }}</dd>
                <dt class="col-sm-3">{{ __('Scheduled') }}</dt><dd class="col-sm-9">{{ $surgery->scheduled_start_at?->format('Y-m-d H:i') ?? '—' }} – {{ $surgery->scheduled_end_at?->format('H:i') ?? '—' }}</dd>
                <dt class="col-sm-3">{{ __('Surgeon / Anesthetist') }}</dt><dd class="col-sm-9">{{ $surgery->primarySurgeon?->name ?? '—' }} / {{ $surgery->anesthetist?->name ?? '—' }}</dd>
                <dt class="col-sm-3">{{ __('Consent') }}</dt><dd class="col-sm-9">{{ $surgery->consent ? 'Obtained (v'.$surgery->consent->version.')' : 'Not obtained' }}</dd>
            </dl>
        </div>
        <div class="card-footer">
            <a href="{{ route('admin.ot.preoperative.show', $surgery) }}" class="btn btn-outline-primary">{{ __('Preoperative') }}</a>
            <a href="{{ route('admin.ot.recovery.show', $surgery) }}" class="btn btn-outline-primary">{{ __('Recovery / PACU') }}</a>
            @if($surgery->status === 'approved')
                @can('ot.schedule.create')
                <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#scheduleForm">{{ __('Schedule') }}</button>
                @endcan
            @endif
            @if(in_array($surgery->status, ['requested','under_review','approved','scheduled','preoperative_preparation']))
                @can('ot.surgery.cancel')
                <form action="{{ route('admin.ot.surgeries.cancel', $surgery) }}" method="post" class="d-inline">@csrf<input name="reason" placeholder="{{ __('Reason') }}" required><button class="btn btn-danger">{{ __('Cancel') }}</button></form>
                @endcan
            @endif
            @if($surgery->status === 'ready_for_ot')
                @can('ot.surgery.update')
                <form action="{{ route('admin.ot.surgeries.receive', $surgery) }}" method="post" class="d-inline">@csrf<button class="btn btn-success">{{ __('Receive Patient') }}</button></form>
                @endcan
            @endif
            @if($surgery->status === 'patient_received')
                @can('ot.surgery.start')
                <form action="{{ route('admin.ot.surgeries.start', $surgery) }}" method="post" class="d-inline">@csrf<button class="btn btn-success">{{ __('Start Procedure') }}</button></form>
                @endcan
            @endif
            @if($surgery->status === 'in_procedure')
                @can('ot.surgery.complete')
                <form action="{{ route('admin.ot.surgeries.complete', $surgery) }}" method="post" class="d-inline">@csrf<button class="btn btn-success">{{ __('Mark Procedure Complete') }}</button></form>
                @endcan
            @endif
        </div>
    </div>

    @if($surgery->status === 'approved')
    <div id="scheduleForm" class="collapse">
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ __('Schedule') }}</h3></div>
            <form action="{{ route('admin.ot.surgeries.schedule', $surgery) }}" method="post">
                @csrf
                <div class="card-body row">
                    <div class="col-md-3"><input type="number" name="theatre_id" class="form-control" placeholder="{{ __('Theatre ID') }}" required></div>
                    <div class="col-md-3"><input type="datetime-local" name="scheduled_start_at" class="form-control" required></div>
                    <div class="col-md-3"><input type="datetime-local" name="scheduled_end_at" class="form-control" required></div>
                    <div class="col-md-3"><input type="number" name="primary_surgeon_id" class="form-control" placeholder="{{ __('Surgeon Provider ID') }}"></div>
                </div>
                <div class="card-footer"><button class="btn btn-primary">{{ __('Confirm Schedule') }}</button></div>
            </form>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Surgical Team') }}</h3></div>
                <div class="card-body">
                    @foreach($surgery->currentTeam as $t)
                        <div>{{ $t->provider?->name }} — {{ ucwords(str_replace('_',' ',$t->role)) }}</div>
                    @endforeach
                    @can('ot.team.assign')
                    <form action="{{ route('admin.ot.team.store', $surgery) }}" method="post" class="mt-2 row">
                        @csrf
                        <div class="col-md-5"><input type="number" name="provider_id" class="form-control" placeholder="{{ __('Provider ID') }}" required></div>
                        <div class="col-md-5">
                            <select name="role" class="form-control">
                                @foreach(['primary_surgeon','assistant_surgeon','anesthetist','anesthesia_assistant','scrub_nurse','circulating_nurse','technician'] as $r)
                                    <option value="{{ $r }}">{{ ucwords(str_replace('_',' ',$r)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2"><button class="btn btn-sm btn-primary">{{ __('Assign') }}</button></div>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Operative Note') }}</h3></div>
                <div class="card-body">
                    @if($surgery->operativeNote)
                        <p><strong>{{ __('Status:') }}</strong> {{ ucfirst($surgery->operativeNote->status) }}</p>
                        <p>{{ $surgery->operativeNote->findings }}</p>
                        @if($surgery->operativeNote->status === 'draft')
                            @can('ot.procedure.complete')
                            <form action="{{ route('admin.ot.operative-note.finalize', $surgery->operativeNote) }}" method="post">@csrf<button class="btn btn-sm btn-success">{{ __('Finalize') }}</button></form>
                            @endcan
                        @endif
                    @else
                        @can('ot.procedure.document')
                        <form action="{{ route('admin.ot.operative-note.store', $surgery) }}" method="post">
                            @csrf
                            <textarea name="findings" class="form-control mb-2" placeholder="Findings"></textarea>
                            <textarea name="technique_summary" class="form-control mb-2" placeholder="Technique summary"></textarea>
                            <button class="btn btn-sm btn-primary">{{ __('Save Draft') }}</button>
                        </form>
                        @endcan
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Specimens') }}</h3></div>
                <div class="card-body">
                    @foreach($surgery->specimens as $s)<div>{{ $s->specimen_number }} — {{ $s->specimen_type }}</div>@endforeach
                    @can('ot.specimen.create')
                    <form action="{{ route('admin.ot.specimens.store', $surgery) }}" method="post" class="mt-2">
                        @csrf
                        <input name="specimen_type" class="form-control mb-1" placeholder="{{ __('Specimen type') }}" required>
                        <button class="btn btn-sm btn-primary">{{ __('Add') }}</button>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Implants') }}</h3></div>
                <div class="card-body">
                    @foreach($surgery->implants as $i)<div>{{ $i->implant_name }} ({{ $i->quantity }})</div>@endforeach
                    @can('ot.implant.record')
                    <form action="{{ route('admin.ot.implants.store', $surgery) }}" method="post" class="mt-2">
                        @csrf
                        <input name="implant_name" class="form-control mb-1" placeholder="{{ __('Implant name') }}" required>
                        <button class="btn btn-sm btn-primary">{{ __('Add') }}</button>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Complications') }}</h3></div>
                <div class="card-body">
                    @foreach($surgery->complications as $c)<div>{{ $c->complication }} ({{ $c->severity }})</div>@endforeach
                    @can('ot.procedure.document')
                    <form action="{{ route('admin.ot.complications.store', $surgery) }}" method="post" class="mt-2">
                        @csrf
                        <input name="complication" class="form-control mb-1" placeholder="{{ __('Complication') }}" required>
                        <button class="btn btn-sm btn-warning">{{ __('Record') }}</button>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
@stop
