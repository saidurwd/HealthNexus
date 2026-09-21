@extends('layouts.adminlte')

@section('page_title', $patient->full_name)

@php
    $currentPath = request()->path();
    $isOverview = $currentPath === 'admin/patients/'.$patient->id || $currentPath === 'admin/patients/'.$patient->id.'/';
@endphp

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
            <div class="d-flex align-items-center">
                <div class="me-4 text-center">
                    @if($patient->profile_picture)
                        <img src="{{ url('storage/'.$patient->profile_picture) }}" alt="{{ $patient->full_name }}" class="img-thumbnail rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                    @else
                        <div class="d-inline-flex align-items-center justify-content-center bg-secondary text-white rounded-circle" style="width: 120px; height: 120px; font-size: 40px;">
                            {{ strtoupper(substr($patient->first_name, 0, 1)) }}{{ $patient->middle_name ? strtoupper(substr($patient->middle_name, 0, 1)) : '' }}{{ strtoupper(substr($patient->last_name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div>
                    <h2 class="mb-1">{{ $patient->full_name }}</h2>
                    <div class="d-flex gap-2 mb-2 flex-wrap">
                        @php
                            $statusClass = match($patient->status) {
                                'active' => 'bg-success',
                                'inactive' => 'bg-warning text-dark',
                                'deceased' => 'bg-danger',
                                default => 'bg-secondary',
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ ucfirst($patient->status) }}</span>
                        @if($patient->blood_group)
                            <span class="badge bg-info text-dark">{{ $patient->blood_group }}</span>
                        @endif
                    </div>
                    <p class="text-muted mb-0">
                        @if($patient->phone)
                            <i class="bi bi-telephone me-2"></i>{{ $patient->phone }}
                            @if($patient->email) <span class="mx-3">|</span> @endif
                        @endif
                        @if($patient->email)
                            <i class="bi bi-envelope me-2"></i>{{ $patient->email }}
                        @endif
                        @if(!$patient->phone && !$patient->email)
                            No contact details recorded
                        @endif
                    </p>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('admin.patients.edit', $patient) }}" class="btn btn-outline-warning">
                    <i class="bi bi-pencil me-1"></i>Edit Patient
                </a>
                <a href="{{ route('admin.patients.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to List
                </a>
            </div>
        </div>

        @php
            $tabs = [
                ['name' => 'Overview', 'route' => route('admin.patients.show', $patient), 'icon' => 'bi-person', 'active' => $isOverview],
                ['name' => 'Timeline', 'route' => route('admin.patients.timeline', $patient), 'icon' => 'bi-clock-history', 'active' => str_contains($currentPath, 'timeline')],
                ['name' => 'Documents', 'route' => route('admin.patients.documents', $patient), 'icon' => 'bi-file-earmark', 'active' => str_contains($currentPath, 'documents')],
                ['name' => 'Allergies', 'route' => route('admin.patients.allergies', $patient), 'icon' => 'bi-bug', 'active' => str_contains($currentPath, 'allergies')],
                ['name' => 'Medical History', 'route' => route('admin.patients.history', $patient), 'icon' => 'bi-journal-medical', 'active' => str_contains($currentPath, 'history')],
            ];
        @endphp

        <ul class="nav nav-pills mb-4 flex-wrap">
            @foreach($tabs as $tab)
                <li class="nav-item">
                    <a href="{{ $tab['route'] }}" class="nav-link {{ $tab['active'] ? 'active' : '' }}">
                        <i class="{{ $tab['icon'] }} me-1"></i>{{ $tab['name'] }}
                    </a>
                </li>
            @endforeach
        </ul>

        @if($isOverview)
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">Patient Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-5">Patient No</dt>
                                        <dd class="col-sm-7">{{ $patient->enterprise_patient_no ?? '-' }}</dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-5">National ID</dt>
                                        <dd class="col-sm-7">{{ $patient->national_identifier ?? '-' }}</dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-5">DOB</dt>
                                        <dd class="col-sm-7">{{ $patient->date_of_birth?->format('Y-m-d') ?? '-' }}</dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-5">Age</dt>
                                        <dd class="col-sm-7">{{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->age.' years' : '-' }}</dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-5">Sex</dt>
                                        <dd class="col-sm-7">{{ $patient->sex ? ['M' => 'Male', 'F' => 'Female', 'O' => 'Other'][$patient->sex] : '-' }}</dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-5">Blood Group</dt>
                                        <dd class="col-sm-7">{{ $patient->blood_group ?? '-' }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">Contact Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-3 col-md-2">Address</dt>
                                        <dd class="col-sm-9 col-md-10">{{ $patient->address ?? '-' }}</dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-5">City</dt>
                                        <dd class="col-sm-7">{{ $patient->city ?? '-' }}</dd>
                                        <dt class="col-sm-5">State</dt>
                                        <dd class="col-sm-7">{{ $patient->state ?? '-' }}</dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-5">Country</dt>
                                        <dd class="col-sm-7">{{ $patient->country ?? '-' }}</dd>
                                        <dt class="col-sm-5">Postal Code</dt>
                                        <dd class="col-sm-7">{{ $patient->postal_code ?? '-' }}</dd>
                                    </dl>
                                </div>
                                <div class="col-12">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-3 col-md-2">Phone</dt>
                                        <dd class="col-sm-9 col-md-10">{{ $patient->phone ?? '-' }}</dd>
                                        <dt class="col-sm-3 col-md-2">Email</dt>
                                        <dd class="col-sm-9 col-md-10">{{ $patient->email ?? '-' }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">Contact Persons</h5>
                        </div>
                        <div class="card-body">
                            @forelse($patient->contacts as $contact)
                                <div class="d-flex justify-content-between align-items-start py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                                    <div>
                                        <strong>{{ $contact->name }}</strong>
                                        @if($contact->relationship)
                                            <span class="text-muted">({{ $contact->relationship }})</span>
                                        @endif
                                        <br>
                                        <small class="text-muted">
                                            @if($contact->phone)
                                                <i class="bi bi-telephone me-1"></i>{{ $contact->phone }}
                                                @if($contact->email) | @endif
                                            @endif
                                            @if($contact->email)
                                                <i class="bi bi-envelope me-1"></i>{{ $contact->email }}
                                            @endif
                                        </small>
                                    </div>
                                    @if($contact->is_emergency)
                                        <span class="badge bg-danger">Emergency Contact</span>
                                    @endif
                                </div>
                            @empty
                                <p class="text-muted mb-0">No contacts recorded.</p>
                            @endforelse
                        </div>
                    </div>

                    @if($patient->identifiers->isNotEmpty())
                        <div class="card mb-4">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0">Identification Documents</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>Type</th>
                                                <th>Value</th>
                                                <th>Authority</th>
                                                <th class="text-end">Issued</th>
                                                <th class="text-end">Expires</th>
                                                <th>Primary</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($patient->identifiers as $identifier)
                                                <tr>
                                                    <td>{{ $identifier->identifier_type }}</td>
                                                    <td>{{ $identifier->identifier_value }}</td>
                                                    <td>{{ $identifier->issuing_authority ?? '-' }}</td>
                                                    <td class="text-end">{{ $identifier->issued_at?->format('M d, Y') ?? '-' }}</td>
                                                    <td class="text-end">{{ $identifier->expires_at?->format('M d, Y') ?? '-' }}</td>
                                                    <td>{{ $identifier->is_primary ? '<span class="badge bg-success">Yes</span>' : 'No' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($patient->allergies->isNotEmpty())
                        <div class="card mb-4">
                            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Allergies</h5>
                                <a href="{{ route('admin.patients.allergies', $patient) }}" class="btn btn-sm btn-outline-light">Manage Allergies</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>Substance</th>
                                                <th>Severity</th>
                                                <th>Reaction</th>
                                                <th class="text-end">Active</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($patient->allergies as $allergy)
                                                <tr>
                                                    <td>{{ $allergy->substance }}</td>
                                                    <td>{{ ucfirst($allergy->severity ?? '') }}</td>
                                                    <td>{{ ucfirst($allergy->reaction ?? '') }}</td>
                                                    <td class="text-end">
                                                        @if($allergy->is_active)
                                                            <span class="badge bg-success">Active</span>
                                                        @else
                                                            <span class="badge bg-secondary">Inactive</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($patient->documents->isNotEmpty())
                        <div class="card mb-4">
                            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Documents</h5>
                                <a href="{{ route('admin.patients.documents', $patient) }}" class="btn btn-sm btn-outline-light">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>File Name</th>
                                                <th>Type</th>
                                                <th class="text-end">Size</th>
                                                <th class="text-end">Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($patient->documents->take(5) as $document)
                                                <tr>
                                                    <td>{{ $document->file_name }}</td>
                                                    <td>{{ ucfirst(str_replace('_', ' ', $document->document_type)) }}</td>
                                                    <td class="text-end">{{ $document->file_size ? number_format($document->file_size / 1024, 1).' KB' : '-' }}</td>
                                                    <td class="text-end"><small class="text-muted">{{ $document->created_at->format('M d, Y') }}</small></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($patient->histories->isNotEmpty())
                        <div class="card mb-4">
                            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Medical History</h5>
                                <a href="{{ route('admin.patients.history', $patient) }}" class="btn btn-sm btn-outline-light">Manage History</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>Condition</th>
                                                <th>Diagnosed</th>
                                                <th class="text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($patient->histories->take(5) as $history)
                                                <tr>
                                                    <td>
                                                        <strong>{{ $history->condition }}</strong>
                                                        @if($history->description)
                                                            <br><small class="text-muted">{{ Str::limit($history->description, 50) }}</small>
                                                        @endif
                                                    </td>
                                                    <td>{{ $history->diagnosed_at ? $history->diagnosed_at->format('M d, Y') : '-' }}</td>
                                                    <td class="text-center">
                                                        @if($history->is_active)
                                                            <span class="badge bg-success">Active</span>
                                                        @else
                                                            <span class="badge bg-secondary">Inactive</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">Quick Stats</h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <div class="text-muted small">Allergies</div>
                                <div class="h4 mb-0 text-danger">{{ $patient->allergies->count() }}</div>
                            </div>
                            <div class="mb-3">
                                <div class="text-muted small">Medical History</div>
                                <div class="h4 mb-0 text-primary">{{ $patient->histories->count() }}</div>
                            </div>
                            <div class="mb-3">
                                <div class="text-muted small">Documents</div>
                                <div class="h4 mb-0 text-info">{{ $patient->documents->count() }}</div>
                            </div>
                            <hr>
                            <div>
                                <div class="text-muted small">Created</div>
                                <div class="small">{{ $patient->created_at->format('M d, Y') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.patients.history', $patient) }}" class="btn btn-outline-primary">
                            <i class="bi bi-journal-medical me-1"></i>View Medical History
                        </a>
                        <a href="{{ route('admin.patients.documents', $patient) }}" class="btn btn-outline-primary">
                            <i class="bi bi-file-earmark me-1"></i>View Documents
                        </a>
                        <a href="{{ route('admin.patients.allergies', $patient) }}" class="btn btn-outline-primary">
                            <i class="bi bi-bug me-1"></i>View Allergies
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
