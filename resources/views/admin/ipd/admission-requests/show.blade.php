@extends('layouts.adminlte')

@section('page_title', 'Admission Request')

@section('page_content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Admission Request — {{ $admissionRequest->patient?->full_name }} <x-ui.status-badge :status="$admissionRequest->status" /></h3>
        <div>
            @can('ipd.admission.approve')
                @if(in_array($admissionRequest->status, ['requested', 'pending_approval']))
                    <form method="POST" action="{{ route('admin.ipd.admission-requests.approve', $admissionRequest) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success">{{ __('Approve') }}</button>
                    </form>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">{{ __('Reject') }}</button>
                @endif
            @endcan
            @if($admissionRequest->status === 'approved' && !$admissionRequest->admission)
                @can('ipd.admission.create')
                    <a href="{{ route('admin.ipd.admissions.create', ['admission_request_id' => $admissionRequest->id]) }}" class="btn btn-primary">{{ __('Admit Patient') }}</a>
                @endcan
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Details') }}</h3></div>
        <div class="card-body">
            <table class="table table-sm mb-0">
                <tr><th>{{ __('Patient') }}</th><td>{{ $admissionRequest->patient?->full_name }}</td></tr>
                <tr><th>{{ __('Admission Type') }}</th><td>{{ $admissionRequest->admissionType?->name ?? '—' }}</td></tr>
                <tr><th>{{ __('Admission Source') }}</th><td>{{ $admissionRequest->admissionSource?->name ?? '—' }}</td></tr>
                <tr><th>{{ __('Priority') }}</th><td>{{ ucfirst($admissionRequest->priority) }}</td></tr>
                <tr><th>{{ __('Reason') }}</th><td>{{ $admissionRequest->reason ?? '—' }}</td></tr>
                <tr><th>{{ __('Provisional Diagnosis') }}</th><td>{{ $admissionRequest->provisional_diagnosis ?? '—' }}</td></tr>
                <tr><th>{{ __('Expected Admission Date') }}</th><td>{{ $admissionRequest->expected_admission_date?->format('Y-m-d') ?? '—' }}</td></tr>
                <tr><th>{{ __('Expected Discharge Date') }}</th><td>{{ $admissionRequest->expected_discharge_date?->format('Y-m-d') ?? '—' }}</td></tr>
                <tr><th>{{ __('Isolation Requirement') }}</th><td>{{ $admissionRequest->isolation_requirement ?? '—' }}</td></tr>
                <tr><th>{{ __('Requested By') }}</th><td>{{ $admissionRequest->requestedBy?->name }}</td></tr>
                @if($admissionRequest->status === 'approved')
                    <tr><th>{{ __('Approved By') }}</th><td>{{ $admissionRequest->approvedBy?->name }} at {{ $admissionRequest->approved_at?->format('Y-m-d H:i') }}</td></tr>
                @endif
                @if($admissionRequest->status === 'rejected')
                    <tr><th>{{ __('Rejection Reason') }}</th><td>{{ $admissionRequest->rejection_reason }}</td></tr>
                @endif
            </table>
        </div>
    </div>

    @can('ipd.admission.approve')
        <div class="modal fade" id="rejectModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.ipd.admission-requests.reject', $admissionRequest) }}">
                        @csrf
                        <div class="modal-header"><h5 class="modal-title">{{ __('Reject Admission Request') }}</h5></div>
                        <div class="modal-body">
                            <label class="form-label">{{ __('Reason') }}</label>
                            <textarea name="reason" class="form-control" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                            <button type="submit" class="btn btn-danger">{{ __('Reject') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@stop
