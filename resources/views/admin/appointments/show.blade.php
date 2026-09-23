@extends('layouts.adminlte')

@section('page_title', 'Appointment Details')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Appointment #{{ $appointment->appointment_no }}</h2>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.opd.consultation', $appointment) }}" class="btn btn-primary">
                    <i class="bi bi-clipboard-medical me-1"></i>Open Consultation
                </a>
                <a href="{{ route('admin.appointments.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Appointment Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Patient</dt>
                                    <dd class="col-sm-8">{{ $appointment->patient->full_name ?? '-' }}</dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Doctor</dt>
                                    <dd class="col-sm-8">{{ $appointment->doctor->name ?? '-' }}</dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Date</dt>
                                    <dd class="col-sm-8">{{ $appointment->appointment_date->format('M d, Y') }}</dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Time</dt>
                                    <dd class="col-sm-8">{{ $appointment->appointment_time->format('g:i A') }}</dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Status</dt>
                                    <dd class="col-sm-8">
                                        @php
                                            $statusClass = match($appointment->status) {
                                                'scheduled' => 'bg-secondary',
                                                'confirmed' => 'bg-info',
                                                'checked_in' => 'bg-warning text-dark',
                                                'in_progress' => 'bg-primary',
                                                'completed' => 'bg-success',
                                                'cancelled' => 'bg-danger',
                                                'no_show' => 'bg-dark',
                                                default => 'bg-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</span>
                                    </dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Token</dt>
                                    <dd class="col-sm-8">{{ $appointment->token->token_number ?? '-' }}</dd>
                                </dl>
                            </div>
                            <div class="col-12">
                                <dl class="row mb-0">
                                    <dt class="col-sm-2">Reason</dt>
                                    <dd class="col-sm-10">{{ $appointment->reason ?? '-' }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                @if($appointment->vitalSigns->isNotEmpty())
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">Vital Signs</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th>Date</th>
                                            <th>Temp (°C)</th>
                                            <th>BP (mmHg)</th>
                                            <th>Pulse</th>
                                            <th>RR</th>
                                            <th>Height (cm)</th>
                                            <th>Weight (kg)</th>
                                            <th>Oxygen %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($appointment->vitalSigns as $vital)
                                            <tr>
                                                <td>{{ $vital->recorded_at->format('M d, Y') }}</td>
                                                <td>{{ $vital->temperature ?? '-' }}</td>
                                                <td>{{ $vital->systolic ? $vital->systolic.'/'.$vital->diastolic : '-' }}</td>
                                                <td>{{ $vital->pulse_rate ?? '-' }}</td>
                                                <td>{{ $vital->respiratory_rate ?? '-' }}</td>
                                                <td>{{ $vital->height ?? '-' }}</td>
                                                <td>{{ $vital->weight ?? '-' }}</td>
                                                <td>{{ $vital->oxygen_saturation ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                @if($appointment->diagnoses->isNotEmpty())
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">Diagnoses</h5>
                        </div>
                        <div class="card-body">
                            @foreach($appointment->diagnoses as $diagnosis)
                                <div class="mb-2 {{ !$loop->last ? 'border-bottom pb-2' : '' }}">
                                    <strong>{{ $diagnosis->description }}</strong>
                                    @if($diagnosis->code)
                                        <span class="text-muted">({{ $diagnosis->code_type }}: {{ $diagnosis->code }})</span>
                                    @endif
                                    <br>
                                    <small class="text-muted">{{ $diagnosis->recorded_at?->format('M d, Y') }}</small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($appointment->prescriptions->isNotEmpty())
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">Prescriptions</h5>
                        </div>
                        <div class="card-body">
                            @foreach($appointment->prescriptions as $prescription)
                                <div class="mb-3 {{ !$loop->last ? 'border-bottom pb-3' : '' }}">
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ $prescription->prescription_no }}</strong>
                                        <small class="text-muted">{{ $prescription->prescribed_at?->format('M d, Y') }}</small>
                                    </div>
                                    @if($prescription->clinical_notes)
                                        <p class="small mt-1">{{ $prescription->clinical_notes }}</p>
                                    @endif
                                    <table class="table table-sm table-bordered mt-2">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>Medicine</th>
                                                <th>Dose</th>
                                                <th>Frequency</th>
                                                <th>Duration</th>
                                                <th>Qty</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($prescription->items as $item)
                                                <tr>
                                                    <td>{{ $item->medicine_name }} {{ $item->strength ? '('.$item->strength.')' : '' }}</td>
                                                    <td>{{ $item->dosage_form ?? '-' }}</td>
                                                    <td>{{ $item->frequency }}</td>
                                                    <td>{{ $item->duration }}</td>
                                                    <td>{{ $item->quantity ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Investigation Orders</h5>
                    </div>
                    <div class="card-body">
                        @forelse($appointment->investigationOrders as $order)
                            <div class="mb-3 {{ !$loop->last ? 'border-bottom pb-3' : '' }}">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ $order->test_name }}</strong>
                                    <span class="badge bg-{{ $order->priority === 'urgent' ? 'warning' : ($order->priority === 'stat' ? 'danger' : 'info') }}">{{ ucfirst($order->priority) }}</span>
                                </div>
                                <small class="text-muted">{{ $order->category ?? 'General' }}</small>
                            </div>
                        @empty
                            <p class="text-muted">No investigation orders.</p>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Documents</h5>
                        <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                            <i class="bi bi-upload"></i>
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <tbody>
                                @forelse($appointment->documents as $document)
                                    <tr>
                                        <td>{{ ucfirst(str_replace('_', ' ', $document->document_type)) }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.appointments.documents.download', [$appointment, $document]) }}" class="btn btn-sm btn-outline-info"><i class="bi bi-download"></i></a>
                                            <form action="{{ route('admin.appointments.documents.delete', [$appointment, $document]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this document?')">
                                                @csrf
                                                @method('delete')
                                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="text-center py-3 text-muted">No documents attached.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="uploadDocModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.appointments.documents.upload', $appointment) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Document</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Document Type *</label>
                            <select name="document_type" class="form-select" required>
                                <option value="referral_letter">Referral Letter</option>
                                <option value="booking_confirmation">Booking Confirmation</option>
                                <option value="external_medical_record">External Medical Record</option>
                                <option value="supporting_document">Supporting Document</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">File *</label>
                            <input type="file" name="document" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
