@extends('layouts.adminlte')

@section('page_title', 'Medication Administration Record')

@section('page_content')
    @php($actionable = ! in_array($mar->status, \App\Models\Nursing\NursingMedicationAdministration::TERMINAL_STATUSES, true))
    <div class="card">
        <div class="card-header"><h3 class="card-title">Safety Review</h3></div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Patient</dt><dd class="col-sm-9">{{ $mar->patient?->full_name }}</dd>
                <dt class="col-sm-3">Allergies</dt>
                <dd class="col-sm-9">{{ $mar->patient?->allergies?->where('is_active', true)->pluck('substance')->implode(', ') ?: 'None recorded' }}</dd>
                <dt class="col-sm-3">Medication</dt><dd class="col-sm-9">{{ $mar->medication?->name ?? 'Ad-hoc (no dispensing)' }}
                    @if($mar->medication?->is_controlled)<span class="badge bg-danger">Controlled</span>@endif
                    @if($mar->medication?->is_high_alert)<span class="badge bg-warning">High-alert</span>@endif</dd>
                <dt class="col-sm-3">Dose / Route</dt><dd class="col-sm-9">{{ $mar->dose ?? '—' }} {{ $mar->dose_unit }} / {{ $mar->route ?? '—' }}</dd>
                <dt class="col-sm-3">Batch / Expiry</dt><dd class="col-sm-9">{{ $mar->batch?->batch_number ?? '—' }} / {{ $mar->batch?->expiry_date?->format('Y-m-d') ?? '—' }}</dd>
                <dt class="col-sm-3">Scheduled</dt><dd class="col-sm-9">{{ $mar->scheduled_at }}</dd>
                <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ ucfirst($mar->status) }} {{ $mar->reason_if_not_administered }}</dd>
                <dt class="col-sm-3">Administered by</dt><dd class="col-sm-9">{{ $mar->administeredBy?->name ?? '—' }} @if($mar->witnessedBy)(witness: {{ $mar->witnessedBy->name }})@endif</dd>
            </dl>
        </div>
    </div>

    @if($actionable)
    <div class="card">
        <div class="card-header"><h3 class="card-title">Administer</h3></div>
        <form action="{{ route('admin.nursing.mar.administer', $mar) }}" method="post">
            @csrf
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-md-4"><input name="dose" class="form-control" placeholder="Dose" value="{{ $mar->dose }}"></div>
                    <div class="col-md-4"><input name="route" class="form-control" placeholder="Route" value="{{ $mar->route }}"></div>
                    <div class="col-md-4"><input name="site" class="form-control" placeholder="Site"></div>
                </div>
                <div class="mb-2">
                    @foreach(['patient','medication','dose','route','time','documentation'] as $check)
                        <label class="mr-3"><input type="checkbox" name="safety_checks[{{ $check }}]" value="1" required> Right {{ $check }}</label>
                    @endforeach
                </div>
                @if($mar->medication?->is_controlled || $mar->medication?->is_high_alert)
                    <input type="number" name="witness_id" class="form-control" placeholder="Witness nurse user ID (required)">
                @endif
            </div>
            <div class="card-footer"><button class="btn btn-success">Confirm Administration</button></div>
        </form>
    </div>

    <div class="row">
        @foreach(['hold' => 'Hold', 'refuse' => 'Refused', 'omit' => 'Omit'] as $action => $label)
            <div class="col-md-4">
                <div class="card">
                    <form action="{{ route('admin.nursing.mar.'.$action, $mar) }}" method="post">
                        @csrf
                        <div class="card-body"><input name="reason" class="form-control" placeholder="Reason for {{ strtolower($label) }}" required></div>
                        <div class="card-footer"><button class="btn btn-warning">{{ $label }}</button></div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
    @else
        @can('nursing.mar.correct')
        <div class="card">
            <div class="card-header"><h3 class="card-title">Record Correction</h3></div>
            <form action="{{ route('admin.nursing.mar.correct', $mar) }}" method="post">
                @csrf
                <div class="card-body"><input name="reason" class="form-control" placeholder="Reason for correction" required></div>
                <div class="card-footer"><button class="btn btn-secondary">Submit Correction</button></div>
            </form>
        </div>
        @endcan
    @endif

    @if($mar->corrections->isNotEmpty())
        <div class="card">
            <div class="card-header"><h3 class="card-title">Corrections</h3></div>
            <div class="card-body">
                @foreach($mar->corrections as $c)<div>{{ $c->created_at }} — {{ $c->reason }}</div>@endforeach
            </div>
        </div>
    @endif
@stop
