@extends('layouts.adminlte')

@section('page_title', 'Lab Order — '.$order->order_number)

@section('page_content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $order->order_number }}</h3>
                    <div class="card-tools">
                        <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th>Patient</th><td>{{ $order->patient?->full_name }} ({{ $order->patient?->enterprise_patient_no ?? $order->patient_id }})</td></tr>
                        <tr><th>Encounter</th><td>{{ $order->encounter?->encounter_no }}</td></tr>
                        <tr><th>Priority</th><td>{{ ucfirst($order->priority) }}</td></tr>
                        <tr><th>Ordered At</th><td>{{ $order->ordered_at?->format('Y-m-d H:i') }}</td></tr>
                        <tr><th>Clinical Notes</th><td>{{ $order->clinical_notes }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Requested Tests</h3></div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr><th>Test</th><th>Status</th><th>Specimen</th><th>Result</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            @forelse($order->items as $item)
                                <tr>
                                    <td>
                                        {{ $item->test?->name ?? $item->requested_test_name }}
                                        @if($item->isUnmatched())
                                            <span class="badge bg-warning">Unmatched</span>
                                        @endif
                                    </td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $item->status)) }}</td>
                                    <td>{{ $item->specimen?->accession_number }}</td>
                                    <td>
                                        @php($result = $item->results->firstWhere('is_current', true))
                                        @if($result)
                                            {{ $result->numeric_value ?? $result->qualitative_value ?? $result->text_value }} {{ $result->unit }}
                                            @if($result->abnormal_flag && $result->abnormal_flag !== 'normal')
                                                <span class="badge bg-danger">{{ strtoupper($result->abnormal_flag) }}</span>
                                            @endif
                                            <br><small>{{ ucfirst(str_replace('_', ' ', $result->result_status)) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->isUnmatched())
                                            @can('lab.order.view')
                                                <form method="post" action="{{ route('admin.lab.orders.resolve-item', $order) }}" class="d-flex gap-1">
                                                    @csrf
                                                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                    <select name="test_id" class="form-control form-control-sm" required>
                                                        <option value="">Match to test...</option>
                                                        @foreach($availableTests as $test)
                                                            <option value="{{ $test->id }}">{{ $test->code }} — {{ $test->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="btn btn-xs btn-primary">Match</button>
                                                </form>
                                            @endcan
                                        @elseif(in_array($item->status, ['received', 'processing']) && ! $result)
                                            @can('lab.result.create')
                                                <form method="post" action="{{ route('admin.lab.results.store', $item) }}" class="d-flex gap-1">
                                                    @csrf
                                                    @if(in_array($item->test->test_type, ['quantitative', 'semi_quantitative', 'calculated']))
                                                        <input type="number" step="0.0001" name="numeric_value" class="form-control form-control-sm" placeholder="Value" required>
                                                    @elseif(in_array($item->test->test_type, ['qualitative']))
                                                        <input type="text" name="qualitative_value" class="form-control form-control-sm" placeholder="Positive/Negative" required>
                                                    @else
                                                        <input type="text" name="text_value" class="form-control form-control-sm" placeholder="Result" required>
                                                    @endif
                                                    <button type="submit" class="btn btn-xs btn-primary">Enter</button>
                                                </form>
                                            @endcan
                                        @elseif($result && $result->result_status === 'entered')
                                            @can('lab.result.validate')
                                                <form method="post" action="{{ route('admin.lab.results.technical-validate', $result) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs btn-success">Technical Validate</button>
                                                </form>
                                            @endcan
                                        @elseif($result && $result->result_status === 'technically_validated')
                                            @can('lab.result.approve')
                                                <form method="post" action="{{ route('admin.lab.results.approve', $result) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs btn-success">Pathologist Approve</button>
                                                </form>
                                            @endcan
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center">No items.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Specimens</h3></div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead><tr><th>Accession #</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            @forelse($order->specimens as $specimen)
                                <tr>
                                    <td><a href="{{ route('admin.lab.specimens.show', $specimen) }}">{{ $specimen->accession_number }}</a></td>
                                    <td>{{ $specimen->specimenType?->name }}</td>
                                    <td>{{ ucfirst($specimen->status) }}</td>
                                    <td>
                                        @if($specimen->status === 'collected')
                                            @can('lab.specimen.receive')
                                                <form method="post" action="{{ route('admin.lab.specimens.receive', $specimen) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs btn-success">Receive</button>
                                                </form>
                                            @endcan
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center">No specimens collected yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(in_array($order->status, ['registered', 'awaiting_collection']))
                    @can('lab.specimen.collect')
                        <div class="card-footer">
                            <form method="post" action="{{ route('admin.lab.specimens.collect', $order) }}" class="row g-2">
                                @csrf
                                <div class="col-md-4">
                                    <input type="text" name="storage_location" class="form-control" placeholder="Storage location (optional)">
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary">Collect Specimen</button>
                                </div>
                            </form>
                        </div>
                    @endcan
                @endif
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Actions</h3></div>
                <div class="card-body">
                    @if($order->status === 'validated')
                        @can('lab.report.generate')
                            <form method="post" action="{{ route('admin.lab.reports.finalize', $order) }}">
                                @csrf
                                <button type="submit" class="btn btn-success w-100 mb-2">Finalize Report</button>
                            </form>
                        @endcan
                    @endif

                    @if(!in_array($order->status, ['reported', 'cancelled']))
                        @can('lab.order.cancel')
                            <form method="post" action="{{ route('admin.lab.orders.cancel', $order) }}" onsubmit="return confirm('Cancel this lab order?');">
                                @csrf
                                <input type="text" name="reason" class="form-control mb-2" placeholder="Cancellation reason" required>
                                <button type="submit" class="btn btn-danger w-100">Cancel Order</button>
                            </form>
                        @endcan
                    @endif
                </div>
            </div>

            @if($order->reports->isNotEmpty())
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Reports</h3></div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach($order->reports as $report)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="{{ route('admin.lab.reports.show', $report) }}">{{ $report->report_number }}</a>
                                    <span class="badge bg-secondary">{{ ucfirst($report->status) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
@stop
