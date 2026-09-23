@extends('layouts.adminlte')

@section('page_title', 'Recall '.$recall->recall_number)

@section('page_content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Recall {{ $recall->recall_number }} <span class="badge bg-info">{{ ucfirst($recall->status) }}</span></h3>
        @if($recall->status === 'initiated')
            <form method="POST" action="{{ route('admin.pharmacy.recalls.close', $recall) }}">
                @csrf
                <button type="submit" class="btn btn-secondary">Close Recall</button>
            </form>
        @endif
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Details</h3></div>
        <div class="card-body">
            <table class="table table-sm mb-0">
                <tr><th>Medication</th><td>{{ $recall->medication?->name }}</td></tr>
                <tr><th>Batch</th><td>{{ $recall->batch?->batch_number }}</td></tr>
                <tr><th>Reason</th><td>{{ $recall->reason }}</td></tr>
                <tr><th>Initiated By</th><td>{{ $recall->initiatedBy?->name }} at {{ $recall->initiated_at?->format('Y-m-d H:i') }}</td></tr>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Affected Dispensings</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>Patient</th><th>Dispensing #</th><th>Quantity</th><th>Date</th></tr></thead>
                <tbody>
                    @forelse($affectedItems as $item)
                        <tr>
                            <td>{{ $item->dispensing?->patient?->full_name }}</td>
                            <td>{{ $item->dispensing?->dispensing_number }}</td>
                            <td>{{ $item->quantity_dispensed }}</td>
                            <td>{{ $item->dispensing?->dispensed_at?->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No patients received this batch.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
