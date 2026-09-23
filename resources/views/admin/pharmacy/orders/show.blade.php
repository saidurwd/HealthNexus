@extends('layouts.adminlte')

@section('page_title', 'Pharmacy Order '.$order->order_number)

@section('page_content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Order {{ $order->order_number }} <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span></h3>
        <div>
            @can('review', $order)
                @if(!in_array($order->status, ['fully_dispensed', 'cancelled', 'rejected']))
                    <a href="{{ route('admin.pharmacy.dispensing.create', $order) }}" class="btn btn-success">Dispense</a>
                @endif
            @endcan
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Patient</h3></div>
                <div class="card-body">
                    <p><strong>{{ $order->patient?->full_name }}</strong></p>
                    <p class="text-muted mb-1">{{ $order->patient?->enterprise_patient_no }}</p>
                    @if($order->patient?->allergies->where('is_active', true)->isNotEmpty())
                        <div class="alert alert-danger p-2 mb-0">
                            <strong>Allergies:</strong>
                            @foreach($order->patient->allergies->where('is_active', true) as $allergy)
                                <span class="badge bg-danger">{{ $allergy->substance }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Order Details</h3></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><th>Prescription</th><td>{{ $order->prescription?->prescription_no }}</td></tr>
                        <tr><th>Ordered At</th><td>{{ $order->ordered_at?->format('Y-m-d H:i') }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Order Items</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>Requested</th><th>Matched Medication</th><th>Qty Prescribed</th><th>Qty Dispensed</th><th>Status</th><th>Safety Alerts</th><th></th></tr></thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td>{{ $item->requested_medicine_name }}</td>
                            <td>{{ $item->medication?->name ?? '—' }}</td>
                            <td>{{ $item->quantity_prescribed ?? '—' }}</td>
                            <td>{{ $item->quantity_dispensed }}</td>
                            <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span></td>
                            <td>
                                @foreach($item->safetyAlerts->where('is_overridden', false) as $alert)
                                    <span class="badge bg-danger" title="{{ $alert->explanation }}">{{ ucfirst($alert->alert_type) }}</span>
                                @endforeach
                            </td>
                            <td>
                                @if($item->status === 'unmatched')
                                    @can('review', $order)
                                        <form method="POST" action="{{ route('admin.pharmacy.orders.resolve-item', $order) }}" class="d-flex gap-1">
                                            @csrf
                                            <input type="hidden" name="item_id" value="{{ $item->id }}">
                                            <select name="medication_id" class="form-control form-control-sm" required>
                                                <option value="">Match to...</option>
                                                @foreach($availableMedications as $medication)
                                                    <option value="{{ $medication->id }}">{{ $medication->name }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-xs btn-primary">Match</button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($order->dispensings->isNotEmpty())
        <div class="card">
            <div class="card-header"><h3 class="card-title">Dispensing History</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Dispensing #</th><th>Status</th><th>Dispensed At</th><th></th></tr></thead>
                    <tbody>
                        @foreach($order->dispensings as $dispensing)
                            <tr>
                                <td>{{ $dispensing->dispensing_number }}</td>
                                <td><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $dispensing->status)) }}</span></td>
                                <td>{{ $dispensing->dispensed_at?->format('Y-m-d H:i') }}</td>
                                <td><a href="{{ route('admin.pharmacy.dispensing.show', $dispensing) }}" class="btn btn-xs btn-info">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @can('cancel', $order)
        @if(!in_array($order->status, ['fully_dispensed', 'cancelled', 'rejected']))
            <form method="POST" action="{{ route('admin.pharmacy.orders.cancel', $order) }}" onsubmit="return confirm('Cancel this order?')">
                @csrf
                <input type="text" name="reason" class="form-control d-inline-block w-auto" placeholder="Cancellation reason" required style="max-width: 300px;">
                <button type="submit" class="btn btn-danger">Cancel Order</button>
            </form>
        @endif
    @endcan
@stop
