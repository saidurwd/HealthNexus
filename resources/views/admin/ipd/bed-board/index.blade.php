@extends('layouts.adminlte')

@section('page_title', 'Bed Board')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Bed Board</h3>
            <div class="card-tools">
                <form method="GET" class="d-flex gap-2">
                    <select name="ward_id" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">All Wards</option>
                        @foreach($wards as $ward)
                            <option value="{{ $ward->id }}" {{ request('ward_id') == $ward->id ? 'selected' : '' }}>{{ $ward->name }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        @foreach(['available','reserved','occupied','cleaning','blocked','maintenance','isolation','out_of_service'] as $status)
                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
        <div class="card-body">
            @forelse($wardsGrouped as $wardName => $rooms)
                <h5 class="mt-3">{{ $wardName }}</h5>
                <div class="row">
                    @foreach($rooms as $roomNumber => $bedsInRoom)
                        <div class="col-md-3 mb-3">
                            <div class="card card-outline card-secondary h-100">
                                <div class="card-header py-1"><strong>Room {{ $roomNumber }}</strong></div>
                                <div class="card-body p-2">
                                    @foreach($bedsInRoom as $bed)
                                        @php($statusColor = match($bed->status) {
                                            'available' => 'bg-success',
                                            'occupied' => 'bg-danger',
                                            'reserved' => 'bg-warning',
                                            'cleaning' => 'bg-info',
                                            default => 'bg-secondary',
                                        })
                                        <div class="border rounded p-2 mb-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong>{{ $bed->bed_code }}</strong>
                                                <span class="badge {{ $statusColor }}">{{ ucfirst(str_replace('_', ' ', $bed->status)) }}</span>
                                            </div>
                                            @if($bed->isolation_capable)
                                                <span class="badge bg-dark">Isolation</span>
                                            @endif
                                            @if($bed->currentAllocation && $bed->currentAllocation->admission)
                                                @can('ipd.admission.view')
                                                    <div class="small mt-1">
                                                        <a href="{{ route('admin.ipd.admissions.show', $bed->currentAllocation->admission) }}">
                                                            {{ $bed->currentAllocation->admission->patient?->full_name ?? 'Patient' }}
                                                        </a>
                                                        <br>{{ $bed->currentAllocation->admission->admission_number }}
                                                        <br>Exp. discharge: {{ $bed->currentAllocation->admission->expected_discharge_date?->format('Y-m-d') ?? '—' }}
                                                    </div>
                                                @endcan
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @empty
                <p class="text-muted">No beds match the current filters.</p>
            @endforelse
        </div>
    </div>
@stop
