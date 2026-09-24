@extends('layouts.adminlte')

@section('page_title', 'Discharge Detail')

@section('page_content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Discharge — {{ $dischargeRequest->patient?->full_name }} <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $dischargeRequest->status)) }}</span></h3>
        @can('complete', $dischargeRequest)
            @if($dischargeRequest->status === 'approved')
                <form method="POST" action="{{ route('admin.ipd.discharge.complete', $dischargeRequest) }}">
                    @csrf
                    <button type="submit" class="btn btn-success">Complete Discharge</button>
                </form>
            @endif
        @endcan
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Details</h3></div>
        <div class="card-body">
            <table class="table table-sm mb-0">
                <tr><th>Admission</th><td>{{ $dischargeRequest->admission?->admission_number }}</td></tr>
                <tr><th>Current Bed</th><td>{{ $dischargeRequest->admission?->currentAllocation?->bed?->bed_code ?? '—' }}</td></tr>
                <tr><th>Discharge Type</th><td>{{ ucfirst($dischargeRequest->discharge_type) }}</td></tr>
                <tr><th>Disposition</th><td>{{ $dischargeRequest->disposition?->name ?? '—' }}</td></tr>
                <tr><th>Planned Date</th><td>{{ $dischargeRequest->planned_date?->format('Y-m-d') ?? '—' }}</td></tr>
                <tr><th>Discharge Diagnosis</th><td>{{ $dischargeRequest->discharge_diagnosis ?? '—' }}</td></tr>
                <tr><th>Instructions</th><td>{{ $dischargeRequest->instructions ?? '—' }}</td></tr>
                <tr><th>Follow-up</th><td>{{ $dischargeRequest->follow_up_required ? 'Required — '.($dischargeRequest->follow_up_date?->format('Y-m-d') ?? 'date TBD') : 'Not required' }}</td></tr>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Clearances</h3></div>
        <div class="card-body">
            <div class="row">
                @foreach(['clinical' => 'Clinical', 'billing' => 'Billing', 'pharmacy' => 'Pharmacy'] as $key => $label)
                    <div class="col-md-4 mb-2">
                        <div class="d-flex justify-content-between align-items-center border rounded p-2">
                            <span>{{ $label }}</span>
                            @if($dischargeRequest->{$key.'_cleared_at'})
                                <span class="badge bg-success">Cleared</span>
                            @else
                                @can('approve', $dischargeRequest)
                                    <form method="POST" action="{{ route('admin.ipd.discharge.clearance', $dischargeRequest) }}">
                                        @csrf
                                        <input type="hidden" name="type" value="{{ $key }}">
                                        <button type="submit" class="btn btn-xs btn-warning">Clear</button>
                                    </form>
                                @else
                                    <span class="badge bg-secondary">Pending</span>
                                @endcan
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@stop
