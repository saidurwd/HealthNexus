@extends('layouts.adminlte')

@section('page_title', 'Medication History — '.$patient->full_name)

@section('page_content')
    <h3>{{ $patient->full_name }} — Medication History</h3>

    @if($allergies->isNotEmpty())
        <div class="alert alert-danger">
            <strong>{{ __('Allergies:') }}</strong>
            @foreach($allergies as $allergy)
                <span class="badge bg-danger">{{ $allergy->substance }} @if($allergy->severity)({{ ucfirst($allergy->severity) }})@endif</span>
            @endforeach
        </div>
    @endif

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Prescriptions / Pharmacy Orders') }}</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>{{ __('Order #') }}</th><th>{{ __('Ordered') }}</th><th>{{ __('Status') }}</th><th>{{ __('# Items') }}</th></tr></thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td><a href="{{ route('admin.pharmacy.orders.show', $order) }}">{{ $order->order_number }}</a></td>
                            <td>{{ $order->ordered_at?->format('Y-m-d') }}</td>
                            <td><x-ui.status-badge :status="$order->status" /></td>
                            <td>{{ $order->items->count() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">{{ __('No pharmacy orders for this patient.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Dispensed Medications') }}</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>{{ __('Medication') }}</th><th>{{ __('Batch') }}</th><th>{{ __('Quantity') }}</th><th>{{ __('Dispensed At') }}</th></tr></thead>
                <tbody>
                    @forelse($dispensedItems as $item)
                        <tr>
                            <td>{{ $item->medication?->name }}</td>
                            <td>{{ $item->batch?->batch_number }}</td>
                            <td>{{ $item->quantity_dispensed }} {{ $item->unit }}</td>
                            <td>{{ $item->dispensing?->dispensed_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">{{ __('No dispensed medications for this patient.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
