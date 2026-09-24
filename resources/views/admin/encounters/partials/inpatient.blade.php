@php($admission = \App\Models\Ipd\IpdAdmission::where('encounter_id', $encounter->id)->with(['admissionType', 'currentAllocation.bed.room.ward', 'attendingProvider', 'admittingProvider', 'dischargeDisposition'])->first())

<div class="tab-pane fade" id="inpatient" role="tabpanel">
    <div class="card card-secondary card-outline">
        <div class="card-header">
            <h3 class="card-title">Inpatient Context</h3>
        </div>
        <div class="card-body">
            @if($admission)
                <table class="table table-sm mb-0">
                    <tr><th>Admission #</th><td>{{ $admission->admission_number }}</td></tr>
                    <tr><th>Type</th><td>{{ $admission->admissionType?->name ?? '—' }}</td></tr>
                    <tr><th>Status</th><td><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $admission->status)) }}</span></td></tr>
                    <tr>
                        <th>Current Location</th>
                        <td>
                            @if($admission->currentAllocation?->bed)
                                {{ $admission->currentAllocation->bed->room?->ward?->name }} / {{ $admission->currentAllocation->bed->room?->room_number }} / {{ $admission->currentAllocation->bed->bed_code }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    <tr><th>Admitting Provider</th><td>{{ $admission->admittingProvider?->name ?? '—' }}</td></tr>
                    <tr><th>Attending Provider</th><td>{{ $admission->attendingProvider?->name ?? '—' }}</td></tr>
                    <tr><th>Admitted At</th><td>{{ $admission->admitted_at?->format('Y-m-d H:i') }}</td></tr>
                    <tr><th>Expected Discharge</th><td>{{ $admission->expected_discharge_date?->format('Y-m-d') ?? '—' }}</td></tr>
                    @if($admission->actual_discharge_date)
                        <tr><th>Discharged At</th><td>{{ $admission->actual_discharge_date->format('Y-m-d H:i') }} ({{ $admission->dischargeDisposition?->name ?? '—' }})</td></tr>
                    @endif
                </table>
                @can('ipd.admission.view')
                    <a href="{{ route('admin.ipd.admissions.show', $admission) }}" class="btn btn-xs btn-outline-primary mt-2">Open Admission Workspace</a>
                @endcan
            @else
                <p class="text-muted mb-0">No IPD admission linked to this encounter.</p>
            @endif
        </div>
    </div>
</div>
