@extends('layouts.adminlte')

@section('page_title', $patient->full_name)

@php
    $currentPath = request()->path();
    $isOverview = $currentPath === 'admin/patients/'.$patient->id || $currentPath === 'admin/patients/'.$patient->id.'/';
@endphp

@section('page_content')
    <div class="card">
        <div class="card-header p-0 p-2">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h3 class="card-title">Patient Profile</h3>
                    </div>
                    <div class="col-sm-6">
                        <nav class="navbar navbar-pills float-sm-end">
                            <ul class="navbar-nav">
                                <li class="nav-item {{ $isOverview ? 'active' : '' }}" style="margin-right: 5px;">
                                    <a class="nav-link" href="{{ route('admin.patients.show', $patient) }}" style="padding: 0.375rem 0.75rem;">
                                        <i class="bi bi-person me-1"></i> Overview
                                    </a>
                                </li>
                                <li class="nav-item {{ str_contains($currentPath, 'timeline') ? 'active' : '' }}" style="margin-right: 5px;">
                                    <a class="nav-link" href="{{ route('admin.patients.timeline', $patient) }}" style="padding: 0.375rem 0.75rem;">
                                        <i class="bi bi-clock-history me-1"></i> Timeline
                                    </a>
                                </li>
                                <li class="nav-item {{ str_contains($currentPath, 'documents') ? 'active' : '' }}" style="margin-right: 5px;">
                                    <a class="nav-link" href="{{ route('admin.patients.documents', $patient) }}" style="padding: 0.375rem 0.75rem;">
                                        <i class="bi bi-file-earmark me-1"></i> Documents
                                    </a>
                                </li>
                                <li class="nav-item {{ str_contains($currentPath, 'allergies') ? 'active' : '' }}" style="margin-right: 5px;">
                                    <a class="nav-link" href="{{ route('admin.patients.allergies', $patient) }}" style="padding: 0.375rem 0.75rem;">
                                        <i class="bi bi-bug me-1"></i> Allergies
                                    </a>
                                </li>
                                <li class="nav-item {{ str_contains($currentPath, 'history') && !str_contains($currentPath, 'medical') ? 'active' : '' }}" style="margin-right: 5px;">
                                    <a class="nav-link" href="{{ route('admin.patients.history', $patient) }}" style="padding: 0.375rem 0.75rem;">
                                        <i class="bi bi-journal-medical me-1"></i> Medical History
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="card-tools" style="position: absolute; top: 10px; right: 10px; z-index: 10;">
                <a href="{{ route('admin.patients.edit', $patient) }}" class="btn btn-warning btn-sm">Edit</a>
                <a href="{{ route('admin.patients.index') }}" class="btn btn-secondary btn-sm">Back</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 text-center">
                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto" style="width: 150px; height: 150px; font-size: 60px; color: white;">
                        {{ strtoupper(substr($patient->first_name, 0, 1)) }}
                    </div>
                </div>
                <div class="col-md-8">
                    <dl class="row">
                        <dt class="col-sm-3">Patient No</dt>
                        <dd class="col-sm-9">{{ $patient->enterprise_patient_no }}</dd>

                        <dt class="col-sm-3">National ID</dt>
                        <dd class="col-sm-9">{{ $patient->national_identifier ?? '-' }}</dd>

                        <dt class="col-sm-3">Name</dt>
                        <dd class="col-sm-9">{{ $patient->full_name }}</dd>

                        <dt class="col-sm-3">Date of Birth</dt>
                        <dd class="col-sm-9">{{ $patient->date_of_birth?->format('Y-m-d') ?? '-' }}</dd>

                        <dt class="col-sm-3">Age</dt>
                        <dd class="col-sm-9">{{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->age.' years' : '-' }}</dd>

                        <dt class="col-sm-3">Sex</dt>
                        <dd class="col-sm-9">{{ $patient->sex ? ['M' => 'Male', 'F' => 'Female', 'O' => 'Other'][$patient->sex] : '-' }}</dd>

                        <dt class="col-sm-3">Blood Group</dt>
                        <dd class="col-sm-9">{{ $patient->blood_group ?? '-' }}</dd>

                        <dt class="col-sm-3">Phone</dt>
                        <dd class="col-sm-9">{{ $patient->phone ?? '-' }}</dd>

                        <dt class="col-sm-3">Email</dt>
                        <dd class="col-sm-9">{{ $patient->email ?? '-' }}</dd>

                        <dt class="col-sm-3">Address</dt>
                        <dd class="col-sm-9">{{ $patient->address ?? '-' }}</dd>

                        <dt class="col-sm-3">City</dt>
                        <dd class="col-sm-9">{{ $patient->city ?? '-' }}</dd>

                        <dt class="col-sm-3">State</dt>
                        <dd class="col-sm-9">{{ $patient->state ?? '-' }}</dd>

                        <dt class="col-sm-3">Country</dt>
                        <dd class="col-sm-9">{{ $patient->country ?? '-' }}</dd>

                        <dt class="col-sm-3">Postal Code</dt>
                        <dd class="col-sm-9">{{ $patient->postal_code ?? '-' }}</dd>

                        <dt class="col-sm-3">Status</dt>
                        <dd class="col-sm-9">
                            <span class="badge {{ $patient->status === 'active' ? 'bg-success' : ($patient->status === 'inactive' ? 'bg-warning' : 'bg-danger') }}">
                                {{ ucfirst($patient->status) }}
                            </span>
                        </dd>

                        <dt class="col-sm-3">Created</dt>
                        <dd class="col-sm-9">{{ $patient->created_at->format('Y-m-d H:i') }}</dd>
                    </dl>
                </div>
            </div>

            @if($patient->identifiers->isNotEmpty())
                <div class="mt-4">
                    <h4>Identification Documents</h4>
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Issuing Authority</th>
                                <th>Issued</th>
                                <th>Expires</th>
                                <th>Primary</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($patient->identifiers as $identifier)
                                <tr>
                                    <td>{{ $identifier->identifier_type }}</td>
                                    <td>{{ $identifier->identifier_value }}</td>
                                    <td>{{ $identifier->issuing_authority ?? '-' }}</td>
                                    <td>{{ $identifier->issued_at?->format('Y-m-d') ?? '-' }}</td>
                                    <td>{{ $identifier->expires_at?->format('Y-m-d') ?? '-' }}</td>
                                    <td>{{ $identifier->is_primary ? 'Yes' : 'No' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($patient->contacts->isNotEmpty())
                <div class="mt-4">
                    <h4>Contacts</h4>
                    <div class="row">
                        @foreach($patient->contacts as $contact)
                            <div class="col-md-6 mb-3">
                                <div class="border p-3 rounded">
                                    <strong>{{ $contact->name }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $contact->relationship ?? '' }}</small>
                                    <br>
                                    <i class="bi bi-telephone me-1"></i> {{ $contact->phone ?? '-' }}
                                    <br>
                                    <i class="bi bi-envelope me-1"></i> {{ $contact->email ?? '-' }}
                                    @if ($contact->is_emergency)
                                        <span class="badge bg-danger">Emergency</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($patient->allergies->isNotEmpty())
                <div class="mt-4">
                    <h4>Allergies</h4>
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Substance</th>
                                <th>Severity</th>
                                <th>Reaction</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($patient->allergies as $allergy)
                                <tr>
                                    <td>{{ $allergy->substance }}</td>
                                    <td>{{ ucfirst($allergy->severity ?? '') }}</td>
                                    <td>{{ ucfirst($allergy->reaction ?? '') }}</td>
                                    <td>{{ $allergy->notes ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <a href="{{ route('admin.patients.allergies', $patient) }}" class="btn btn-sm btn-outline-primary">View all allergies</a>
                </div>
            @endif
        </div>
    </div>
@stop
