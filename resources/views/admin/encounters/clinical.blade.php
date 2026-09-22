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
                @endif
                <a href="{{ route('admin.encounters.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>

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
@stop
