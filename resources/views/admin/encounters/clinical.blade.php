@extends('layouts.adminlte')

@section('page_title', 'Encounter Clinical Workspace')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">{{ $encounter->patient->full_name ?? 'N/A' }}</h2>
                <p class="text-muted mb-0">
                    Encounter: {{ $encounter->encounter_no }} ·
                    Type: {{ $encounter->encounter_type }} ·
                    Status: {{ $encounter->status }}
                    @if($encounter->locked_at)
                        · <span class="badge bg-danger">Locked</span>
                    @endif
                </p>
            </div>
            <div class="d-flex gap-2">
                @if(in_array($encounter->status, ['registered', 'waiting']))
                    <form method="POST" action="{{ route('admin.encounters.start', $encounter) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-play-circle me-1"></i>Start Encounter
                        </button>
                    </form>
                    @can('encounter.cancel')
                        <form method="POST" action="{{ route('admin.encounters.cancel', $encounter) }}" class="d-inline" onsubmit="return confirm('Cancel this encounter?')">
                            @csrf
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-x-circle me-1"></i>Cancel
                            </button>
                        </form>
                    @endcan
                @elseif($encounter->status === 'in_progress')
                    <form method="POST" action="{{ route('admin.encounters.pause', $encounter) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning me-1">
                            <i class="bi bi-pause-circle me-1"></i>Pause
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.encounters.complete', $encounter) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>Complete
                        </button>
                    </form>
                    @can('encounter.cancel')
                        <form method="POST" action="{{ route('admin.encounters.transfer', $encounter) }}" class="d-inline" onsubmit="return confirm('Transfer this encounter?')">
                            @csrf
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-arrow-right-circle me-1"></i>Transfer
                            </button>
                        </form>
                    @endcan
                @elseif($encounter->status === 'paused')
                    <form method="POST" action="{{ route('admin.encounters.resume', $encounter) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-play-circle me-1"></i>Resume
                        </button>
                    </form>
                @elseif($encounter->status === 'completed' && !$encounter->locked_at)
                    <form method="POST" action="{{ route('admin.encounters.lock', $encounter) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-lock me-1"></i>Lock Record
                        </button>
                    </form>
                @elseif($encounter->locked_at)
                    @can('clinical.break_glass')
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#breakGlassModal">
                            <i class="bi bi-shield-unlocked me-1"></i>Break Glass
                        </button>
                    @endcan
                @endif
                <a href="{{ route('admin.encounters.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>

        @php($activeAllergies = $encounter->patient?->allergies->where('is_active', true) ?? collect())
        @if($activeAllergies->isNotEmpty())
            <div class="alert alert-danger d-flex align-items-start mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
                <div>
                    <strong>Allergy Alert:</strong>
                    @foreach($activeAllergies as $allergy)
                        <span class="badge bg-danger me-1">
                            {{ $allergy->substance }}
                            @if($allergy->severity) ({{ ucfirst($allergy->severity) }}) @endif
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <h3 class="profile-username text-center">{{ $encounter->patient->full_name ?? 'N/A' }}</h3>
                        <p class="text-muted text-center">
                            {{ $encounter->patient->enterprise_patient_no ?? 'N/A' }}
                        </p>
                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Age</b> <span class="float-right">{{ $encounter->patient->date_of_birth?->age ?? 'N/A' }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Gender</b> <span class="float-right">{{ $encounter->patient->gender ?? 'N/A' }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Blood Group</b> <span class="float-right">{{ $encounter->patient->blood_group ?? 'N/A' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header p-2">
                        <ul class="nav nav-pills">
                            <li class="nav-item"><a class="nav-link active" href="#complaint" data-bs-toggle="tab">Chief Complaint</a></li>
                            <li class="nav-item"><a class="nav-link" href="#history" data-bs-toggle="tab">History</a></li>
                            <li class="nav-item"><a class="nav-link" href="#examination" data-bs-toggle="tab">Examination</a></li>
                            <li class="nav-item"><a class="nav-link" href="#vitals" data-bs-toggle="tab">Vitals</a></li>
                            <li class="nav-item"><a class="nav-link" href="#diagnosis" data-bs-toggle="tab">Diagnosis</a></li>
                            <li class="nav-item"><a class="nav-link" href="#problems" data-bs-toggle="tab">Problems</a></li>
                            <li class="nav-item"><a class="nav-link" href="#orders" data-bs-toggle="tab">Orders</a></li>
                            <li class="nav-item"><a class="nav-link" href="#lab-results" data-bs-toggle="tab">Lab Results</a></li>
                            <li class="nav-item"><a class="nav-link" href="#imaging" data-bs-toggle="tab">Imaging</a></li>
                            <li class="nav-item"><a class="nav-link" href="#medications" data-bs-toggle="tab">Medications</a></li>
                            @if($encounter->encounter_type === 'IPD')
                                <li class="nav-item"><a class="nav-link" href="#inpatient" data-bs-toggle="tab">Inpatient</a></li>
                            @endif
                            <li class="nav-item"><a class="nav-link" href="#prescription" data-bs-toggle="tab">Prescription</a></li>
                            <li class="nav-item"><a class="nav-link" href="#referral" data-bs-toggle="tab">Referral</a></li>
                            <li class="nav-item"><a class="nav-link" href="#instructions" data-bs-toggle="tab">Instructions</a></li>
                            <li class="nav-item"><a class="nav-link" href="#summary" data-bs-toggle="tab">Summary</a></li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            @include('admin.encounters.partials.complaint', compact('encounter'))
                            @include('admin.encounters.partials.history', compact('encounter'))
                            @include('admin.encounters.partials.examination', compact('encounter'))
                            @include('admin.encounters.partials.vitals', compact('encounter'))
                            @include('admin.encounters.partials.diagnosis', compact('encounter'))
                            @include('admin.encounters.partials.problems', compact('encounter'))
                            @include('admin.encounters.partials.orders', compact('encounter'))
                            @include('admin.encounters.partials.lab-results', compact('encounter'))
                            @include('admin.encounters.partials.imaging', compact('encounter'))
                            @include('admin.encounters.partials.medications', compact('encounter'))
                            @if($encounter->encounter_type === 'IPD')
                                @include('admin.encounters.partials.inpatient', compact('encounter'))
                            @endif
                            @include('admin.encounters.partials.prescription', compact('encounter'))
                            @include('admin.encounters.partials.referral', compact('encounter'))
                            @include('admin.encounters.partials.instructions', compact('encounter'))
                            @include('admin.encounters.partials.summary', compact('encounter'))
                        </div>
                    </div>
                </div>
        </div>
    </div>
</div>

<div class="modal fade" id="breakGlassModal" tabindex="-1" aria-labelledby="breakGlassModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.encounters.break-glass', $encounter) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="breakGlassModalLabel">Break-Glass Access</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-warning">You are requesting emergency access to a locked clinical record. This action will be audited.</p>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" id="reason" class="form-control" rows="3" required minlength="10"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="scope" class="form-label">Scope</label>
                        <input type="text" name="scope" id="scope" class="form-control" placeholder="e.g., view, edit, prescription">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-warning">Request Access</button>
                </div>
            </form>
        </div>
    </div>
</div>

@stop
