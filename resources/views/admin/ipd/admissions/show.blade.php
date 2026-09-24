@extends('layouts.adminlte')

@section('page_title', 'Admission '.$admission->admission_number)

@section('page_content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3>{{ $admission->patient?->full_name }} <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $admission->status)) }}</span></h3>
            <p class="text-muted mb-0">
                Admission: {{ $admission->admission_number }} ·
                MRN: {{ $admission->patient?->enterprise_patient_no }} ·
                Admitted: {{ $admission->admitted_at?->format('Y-m-d H:i') }}
            </p>
        </div>
        <div>
            @can('ipd.transfer.create')
                @if(in_array($admission->status, ['active', 'admitted', 'transferred']))
                    <a href="{{ route('admin.ipd.transfers.create', $admission) }}" class="btn btn-warning">Transfer</a>
                @endif
            @endcan
            @can('ipd.discharge.create')
                @if(!in_array($admission->status, ['discharged', 'closed']))
                    <a href="{{ route('admin.ipd.discharge.create', $admission) }}" class="btn btn-primary">Discharge</a>
                @endif
            @endcan
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Current Location</h3></div>
                <div class="card-body">
                    @if($admission->currentAllocation?->bed)
                        <p class="mb-1"><strong>Ward:</strong> {{ $admission->currentAllocation->bed->room?->ward?->name }}</p>
                        <p class="mb-1"><strong>Room:</strong> {{ $admission->currentAllocation->bed->room?->room_number }}</p>
                        <p class="mb-0"><strong>Bed:</strong> {{ $admission->currentAllocation->bed->bed_code }}</p>
                    @else
                        <p class="text-muted mb-0">No active bed allocation.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Care Team</h3></div>
                <div class="card-body">
                    <p class="mb-1"><strong>Admitting:</strong> {{ $admission->admittingProvider?->name ?? '—' }}</p>
                    <p class="mb-0"><strong>Attending:</strong> {{ $admission->attendingProvider?->name ?? '—' }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Discharge Plan</h3></div>
                <div class="card-body">
                    <p class="mb-1"><strong>Expected:</strong> {{ $admission->expected_discharge_date?->format('Y-m-d') ?? '—' }}</p>
                    <p class="mb-0"><strong>Actual:</strong> {{ $admission->actual_discharge_date?->format('Y-m-d H:i') ?? '—' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            @if($admission->encounter)
                <a href="{{ route('admin.encounters.show', $admission->encounter) }}" class="btn btn-outline-primary mb-3">View Clinical Encounter</a>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Movement History</h3></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead><tr><th>Type</th><th>From</th><th>To</th><th>Status</th><th>Moved At</th></tr></thead>
                <tbody>
                    @forelse($admission->movements as $movement)
                        <tr>
                            <td>{{ ucfirst(str_replace('_', ' ', $movement->movement_type)) }}</td>
                            <td>{{ $movement->fromBed?->bed_code ?? '—' }}</td>
                            <td>{{ $movement->toBed?->bed_code ?? '—' }}</td>
                            <td><span class="badge bg-secondary">{{ ucfirst($movement->status) }}</span></td>
                            <td>{{ $movement->moved_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No movements recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($admission->leaves->isNotEmpty())
        <div class="card">
            <div class="card-header"><h3 class="card-title">Leave History</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Type</th><th>Expected Return</th><th>Actual Return</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach($admission->leaves as $leave)
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $leave->leave_type)) }}</td>
                                <td>{{ $leave->expected_return_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $leave->actual_return_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $leave->status)) }}</span></td>
                                <td><a href="{{ route('admin.ipd.leave.show', $leave) }}" class="btn btn-xs btn-info">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Patient Leave</h3>
        </div>
        @can('ipd.leave.create')
            @if(in_array($admission->status, ['active', 'admitted']))
                <form method="POST" action="{{ route('admin.ipd.leave.store', $admission) }}" class="card-body row g-2">
                    @csrf
                    <div class="col-md-3">
                        <input type="text" name="leave_type" class="form-control" placeholder="Leave type" value="temporary_pass" required>
                    </div>
                    <div class="col-md-3">
                        <input type="datetime-local" name="expected_return_at" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <select name="bed_handling" class="form-control">
                            <option value="retain">Retain bed</option>
                            <option value="release">Release bed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-warning w-100">Request Leave</button>
                    </div>
                </form>
            @endif
        @endcan
    </div>
@stop
