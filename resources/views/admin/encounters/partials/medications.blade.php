@php($pharmacyOrders = \App\Models\Pharmacy\PharmacyOrder::where('encounter_id', $encounter->id)->with(['items.medication', 'items.safetyAlerts', 'dispensings.items'])->latest('ordered_at')->get())

<div class="tab-pane fade" id="medications" role="tabpanel">
    <div class="card card-secondary card-outline">
        <div class="card-header">
            <h3 class="card-title">Medications</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Medication</th>
                        <th>Prescribed</th>
                        <th>Dispensed</th>
                        <th>Status</th>
                        <th>Safety Alerts</th>
                        <th>Order</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pharmacyOrders as $order)
                        @forelse($order->items as $item)
                            <tr>
                                <td>{{ $item->medication?->name ?? $item->requested_medicine_name }}</td>
                                <td>{{ $item->quantity_prescribed ?? '—' }}</td>
                                <td>{{ $item->quantity_dispensed }}</td>
                                <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span></td>
                                <td>
                                    @foreach($item->safetyAlerts->where('is_overridden', false) as $alert)
                                        <span class="badge bg-danger">{{ ucfirst($alert->alert_type) }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    @can('pharmacy.prescription.view')
                                        <a href="{{ route('admin.pharmacy.orders.show', $order) }}" class="btn btn-xs btn-outline-primary">{{ $order->order_number }}</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                        @endforelse
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">No pharmacy orders for this encounter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
