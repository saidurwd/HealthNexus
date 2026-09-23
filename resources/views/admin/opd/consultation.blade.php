@extends('layouts.adminlte')

@section('page_title', 'OPD Consultation')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">{{ $appointment->patient->full_name }}</h2>
                <p class="text-muted mb-0">
                    {{ $appointment->patient->enterprise_patient_no ?? 'N/A' }} · DOB: {{ $appointment->patient->date_of_birth?->format('Y-m-d') ?? 'N/A' }} · {{ $appointment->patient->phone ?? 'No phone' }}
                </p>
            </div>
            <div class="d-flex gap-2">
                @if($appointment->status !== 'in_progress')
                    <form method="POST" action="{{ route('admin.opd.status.in-progress', $appointment) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-play-circle me-1"></i>Start Consultation
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.opd.status.completed', $appointment) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>Complete Consultation
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.appointments.show', $appointment) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>

            @if($appointment->status !== 'checked_in' && $appointment->status !== 'in_progress' && $appointment->status !== 'completed')
                <form method="POST" action="{{ route('admin.appointments.check-in', $appointment) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-door-open me-1"></i>Check-in Patient
                    </button>
                </form>
            @endif
        </div>

        @php($activeAllergies = $appointment->patient->allergies->where('is_active', true))
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

        <ul class="nav nav-pills mb-4 flex-wrap" id="consultationTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a href="#vitals" class="nav-link active" data-bs-toggle="pill" data-bs-target="#vitals" role="tab">
                    <i class="bi bi-heart-pulse me-1"></i>Vital Signs
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="#diagnosis" class="nav-link" data-bs-toggle="pill" data-bs-target="#diagnosis" role="tab">
                    <i class="bi bi-clipboard-data me-1"></i>Diagnosis
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="#prescription" class="nav-link" data-bs-toggle="pill" data-bs-target="#prescription" role="tab">
                    <i class="bi bi-capsule me-1"></i>Prescription
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="#investigations" class="nav-link" data-bs-toggle="pill" data-bs-target="#investigations" role="tab">
                    <i class="bi bi-test-tube me-1"></i>Investigations
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="#followup" class="nav-link" data-bs-toggle="pill" data-bs-target="#followup" role="tab">
                    <i class="bi bi-calendar-check me-1"></i>Follow-up
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="vitals" role="tabpanel">
                @if($appointment->vitalSigns->isNotEmpty())
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">Recorded Vitals</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th>Date</th>
                                            <th>Temp (°C)</th>
                                            <th>BP (mmHg)</th>
                                            <th>Pulse</th>
                                            <th>RR</th>
                                            <th>Height</th>
                                            <th>Weight</th>
                                            <th>BMI</th>
                                            <th>O₂ Sat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($appointment->vitalSigns as $vital)
                                            <tr>
                                                <td>{{ $vital->recorded_at->format('M d, Y H:i') }}</td>
                                                <td>{{ $vital->temperature ?? '-' }}</td>
                                                <td>{{ $vital->systolic ? $vital->systolic.'/'.$vital->diastolic : '-' }}</td>
                                                <td>{{ $vital->pulse_rate ?? '-' }}</td>
                                                <td>{{ $vital->respiratory_rate ?? '-' }}</td>
                                                <td>{{ $vital->height ?? '-' }}</td>
                                                <td>{{ $vital->weight ?? '-' }}</td>
                                                <td>{{ $vital->bmi ?? '-' }}</td>
                                                <td>{{ $vital->oxygen_saturation ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Record Vitals</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.opd.vital-signs') }}">
                            @csrf
                            <input type="hidden" name="appointment_id" value="{{ $appointment->id }}">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Temperature (°C)</label>
                                    <input type="number" step="0.1" name="temperature" class="form-control" min="30" max="45">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Systolic (mmHg)</label>
                                    <input type="number" name="systolic" class="form-control" min="50" max="300">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Diastolic (mmHg)</label>
                                    <input type="number" name="diastolic" class="form-control" min="30" max="200">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Pulse Rate (bpm)</label>
                                    <input type="number" name="pulse_rate" class="form-control" min="30" max="250">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Respiratory Rate</label>
                                    <input type="number" name="respiratory_rate" class="form-control" min="8" max="50">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Height (cm)</label>
                                    <input type="number" step="0.1" name="height" class="form-control" min="50" max="300">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Weight (kg)</label>
                                    <input type="number" step="0.1" name="weight" class="form-control" min="20" max="500">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Oxygen Saturation (%)</label>
                                    <input type="number" name="oxygen_saturation" class="form-control" min="0" max="100">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-secondary">
                                    <i class="bi bi-save me-1"></i>Save Vitals
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="diagnosis" role="tabpanel">
                @if($appointment->diagnoses->isNotEmpty())
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">Recorded Diagnoses</h5>
                        </div>
                        <div class="card-body">
                            @foreach($appointment->diagnoses as $diagnosis)
                                <div class="mb-2 {{ !$loop->last ? 'border-bottom pb-2' : '' }}">
                                    <strong>{{ $diagnosis->description }}</strong>
                                    @if($diagnosis->code)
                                        <span class="text-muted">({{ $diagnosis->code }})</span>
                                    @endif
                                    <br>
                                    <span class="badge bg-{{ $diagnosis->status === 'confirmed' ? 'success' : ($diagnosis->status === 'provisional' ? 'warning' : 'secondary') }}">{{ ucfirst($diagnosis->status) }}</span>
                                    <small class="text-muted ms-2">{{ $diagnosis->recorded_at?->format('M d, Y') }}</small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Add Diagnosis</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.opd.diagnoses') }}">
                            @csrf
                            <input type="hidden" name="appointment_id" value="{{ $appointment->id }}">
                            <input type="hidden" name="patient_id" value="{{ $appointment->patient_id }}">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Code Type</label>
                                    <input type="text" name="code_type" class="form-control" placeholder="e.g., ICD-10">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Code</label>
                                    <input type="text" name="code" class="form-control">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description *</label>
                                    <input type="text" name="description" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="confirmed">Confirmed</option>
                                        <option value="provisional">Provisional</option>
                                        <option value="rule_out">Rule Out</option>
                                        <option value="resolved">Resolved</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Recorded At</label>
                                    <input type="date" name="recorded_at" class="form-control" value="{{ date('Y-m-d') }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-secondary">
                                    <i class="bi bi-save me-1"></i>Add Diagnosis
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="prescription" role="tabpanel">
                @if($appointment->prescriptions->isNotEmpty())
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">Prescriptions</h5>
                        </div>
                        <div class="card-body">
                            @foreach($appointment->prescriptions as $prescription)
                                <div class="mb-4 {{ !$loop->last ? 'border-bottom pb-4' : '' }}">
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
                                                <th>Strength</th>
                                                <th>Dose Form</th>
                                                <th>Frequency</th>
                                                <th>Duration</th>
                                                <th>Qty</th>
                                                <th>Instructions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($prescription->items as $item)
                                                <tr>
                                                    <td>{{ $item->medicine_name }}</td>
                                                    <td>{{ $item->strength ?? '-' }}</td>
                                                    <td>{{ $item->dosage_form ?? '-' }}</td>
                                                    <td>{{ $item->frequency }}</td>
                                                    <td>{{ $item->duration }}</td>
                                                    <td>{{ $item->quantity ?? '-' }}</td>
                                                    <td>{{ $item->instructions ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Add Prescription</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.opd.prescriptions') }}" id="prescription-form">
                            @csrf
                            <input type="hidden" name="appointment_id" value="{{ $appointment->id }}">
                            <input type="hidden" name="patient_id" value="{{ $appointment->patient_id }}">
                            <div class="mb-3">
                                <label class="form-label">Clinical Notes</label>
                                <textarea name="clinical_notes" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">General Advice</label>
                                <textarea name="advice" class="form-control" rows="2"></textarea>
                            </div>
                            <div id="prescription-items">
                                <div class="row g-2 mb-2 item-row">
                                    <div class="col-md-3">
                                        <input type="text" name="items[0][medicine_name]" class="form-control" placeholder="Medicine name" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="items[0][strength]" class="form-control" placeholder="Strength">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="items[0][dosage_form]" class="form-control" placeholder="Dosage form">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" name="items[0][frequency]" class="form-control" placeholder="e.g., BID" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="items[0][duration]" class="form-control" placeholder="e.g., 7 days" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addPrescriptionItem()">
                                    <i class="bi bi-plus me-1"></i>Add Medicine
                                </button>
                            </div>
                            <button type="submit" class="btn btn-secondary">
                                <i class="bi bi-save me-1"></i>Save Prescription
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="investigations" role="tabpanel">
                @if($appointment->investigationOrders->isNotEmpty())
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">Investigation Orders</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th>Test</th>
                                            <th>Category</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Ordered</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($appointment->investigationOrders as $order)
                                            <tr>
                                                <td>{{ $order->test_name }}</td>
                                                <td>{{ $order->category ?? '-' }}</td>
                                                <td>{{ ucfirst($order->priority) }}</td>
                                                <td><span class="badge bg-info">{{ ucfirst($order->status) }}</span></td>
                                                <td><small class="text-muted">{{ $order->ordered_at?->format('M d, Y') }}</small></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Add Investigation Order</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.opd.investigation-orders') }}">
                            @csrf
                            <input type="hidden" name="appointment_id" value="{{ $appointment->id }}">
                            <input type="hidden" name="patient_id" value="{{ $appointment->patient_id }}">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Test Name *</label>
                                    <input type="text" name="test_name" class="form-control" required placeholder="e.g., CBC, Blood Sugar">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Category</label>
                                    <input type="text" name="category" class="form-control" placeholder="e.g., Laboratory">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Priority</label>
                                    <select name="priority" class="form-select">
                                        <option value="routine">Routine</option>
                                        <option value="urgent">Urgent</option>
                                        <option value="stat">Stat</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Clinical Notes</label>
                                    <textarea name="clinical_notes" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-secondary">
                                    <i class="bi bi-save me-1"></i>Order Investigation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            <div class="tab-pane fade" id="followup" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Schedule Follow-up Appointment</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.appointments.follow-up', $appointment) }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Follow-up Date *</label>
                                    <input type="date" name="appointment_date" class="form-control" value="{{ old('appointment_date', now()->addDays(7)->toDateString()) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Follow-up Time *</label>
                                    <input type="time" name="appointment_time" class="form-control" value="{{ old('appointment_time', $appointment->appointment_time->format('H:i')) }}" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Reason</label>
                                    <input type="text" name="reason" class="form-control" value="{{ old('reason', $appointment->reason) }}" placeholder="Reason for follow-up...">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-secondary">
                                    <i class="bi bi-calendar-check me-1"></i>Create Follow-up
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
function addPrescriptionItem() {
    prescriptionItemCount++;
    const container = document.getElementById('prescription-items');
    const newRow = document.createElement('div');
    newRow.className = 'row g-2 mb-2 item-row';
    newRow.innerHTML = `
        <div class="col-md-3">
            <input type="text" name="items[${prescriptionItemCount}][medicine_name]" class="form-control" placeholder="Medicine name" required>
        </div>
        <div class="col-md-2">
            <input type="text" name="items[${prescriptionItemCount}][strength]" class="form-control" placeholder="Strength">
        </div>
        <div class="col-md-2">
            <input type="text" name="items[${prescriptionItemCount}][dosage_form]" class="form-control" placeholder="Dosage form">
        </div>
        <div class="col-md-3">
            <input type="text" name="items[${prescriptionItemCount}][frequency]" class="form-control" placeholder="e.g., BID" required>
        </div>
        <div class="col-md-2">
            <input type="text" name="items[${prescriptionItemCount}][duration]" class="form-control" placeholder="e.g., 7 days" required>
        </div>
    `;
    container.appendChild(newRow);
}
</script>
@endsection
