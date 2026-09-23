@extends('layouts.adminlte')

@section('page_title', 'Specimen — '.$specimen->accession_number)

@section('page_content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $specimen->accession_number }}</h3>
                    <div class="card-tools"><span class="badge {{ $specimen->status === 'rejected' ? 'bg-danger' : 'bg-info' }}">{{ ucfirst($specimen->status) }}</span></div>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th>Order</th><td><a href="{{ route('admin.lab.orders.show', $specimen->labOrder) }}">{{ $specimen->labOrder->order_number }}</a></td></tr>
                        <tr><th>Patient</th><td>{{ $specimen->labOrder->patient?->full_name }}</td></tr>
                        <tr><th>Specimen Type</th><td>{{ $specimen->specimenType?->name }}</td></tr>
                        <tr><th>Container</th><td>{{ $specimen->containerType?->name }}</td></tr>
                        <tr><th>Collected By</th><td>{{ $specimen->collectedBy?->name }} at {{ $specimen->collected_at?->format('Y-m-d H:i') }}</td></tr>
                        @if($specimen->received_at)
                            <tr><th>Received By</th><td>{{ $specimen->receivedBy?->name }} at {{ $specimen->received_at?->format('Y-m-d H:i') }}</td></tr>
                        @endif
                        @if($specimen->rejected_at)
                            <tr><th>Rejected By</th><td>{{ $specimen->rejectedBy?->name }} at {{ $specimen->rejected_at?->format('Y-m-d H:i') }}</td></tr>
                            <tr><th>Rejection Reason</th><td>{{ config('laboratory.rejection_reasons')[$specimen->rejection_reason] ?? $specimen->rejection_reason }}</td></tr>
                        @endif
                        <tr><th>Storage Location</th><td>{{ $specimen->storage_location }}</td></tr>
                    </table>

                    <h5>Tests on this specimen</h5>
                    <ul>
                        @foreach($specimen->orderItems as $item)
                            <li>{{ $item->test?->name ?? $item->requested_test_name }} — {{ ucfirst(str_replace('_', ' ', $item->status)) }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-footer">
                    @if($specimen->status === 'collected')
                        @can('lab.specimen.receive')
                            <form method="post" action="{{ route('admin.lab.specimens.receive', $specimen) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success">Receive</button>
                            </form>
                        @endcan
                    @endif
                    @if(in_array($specimen->status, ['pending', 'collected', 'received']))
                        @can('lab.specimen.reject')
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
                        @endcan
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Label</h3></div>
                <div class="card-body text-center">
                    {!! app(\App\Services\Laboratory\LabBarcodeService::class)->svg($specimen) !!}
                    <p class="mt-2">{{ $specimen->accession_number }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="{{ route('admin.lab.specimens.reject', $specimen) }}">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Reject Specimen</h5></div>
                    <div class="modal-body">
                        <label class="form-label">Reason</label>
                        <select name="reason" class="form-control mb-2" required>
                            <option value="">Select a reason</option>
                            @foreach(config('laboratory.rejection_reasons') as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject Specimen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop
