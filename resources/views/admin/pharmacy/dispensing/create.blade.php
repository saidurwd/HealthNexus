@extends('layouts.adminlte')

@section('page_title', 'Dispense — '.$order->order_number)

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Dispense Order {{ $order->order_number }} — {{ $order->patient?->full_name }}</h3>
        </div>

        @if($order->patient?->allergies->where('is_active', true)->isNotEmpty())
            <div class="alert alert-danger m-3">
                <strong>Allergy Alert:</strong>
                @foreach($order->patient->allergies->where('is_active', true) as $allergy)
                    <span class="badge bg-danger">{{ $allergy->substance }}</span>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.pharmacy.dispensing.store', $order) }}">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Dispensing Store</label>
                    <select name="store_id" class="form-control @error('store_id') is-invalid @enderror" required>
                        <option value="">Select Store</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </select>
                    @error('store_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <table class="table table-bordered">
                    <thead><tr><th>Item</th><th>Remaining Qty</th><th>Dispense</th><th>Quantity</th><th>Substitution Reason</th></tr></thead>
                    <tbody>
                        @foreach($order->items->whereNotIn('status', ['cancelled']) as $item)
                            @php($remaining = $item->remainingQuantity())
                            <tr>
                                <td>
                                    {{ $item->medication?->name ?? $item->requested_medicine_name }}
                                    @if(!$item->medication_id)<span class="badge bg-warning">Unmatched</span>@endif
                                </td>
                                <td>{{ $remaining ?? '—' }}</td>
                                <td>
                                    @if($item->medication_id && ($remaining === null || $remaining > 0))
                                        <input type="checkbox" name="lines[{{ $loop->index }}][enabled]" value="1">
                                        <input type="hidden" name="lines[{{ $loop->index }}][order_item_id]" value="{{ $item->id }}">
                                    @endif
                                </td>
                                <td><input type="number" name="lines[{{ $loop->index }}][quantity]" min="1" value="{{ $remaining }}" class="form-control form-control-sm"></td>
                                <td><input type="text" name="lines[{{ $loop->index }}][substitution_reason]" class="form-control form-control-sm" placeholder="If substituting"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="text-muted">Only checked lines with a matched medication are submitted for dispensing. FEFO batch selection is automatic.</p>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.pharmacy.orders.show', $order) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-success">Dispense</button>
            </div>
        </form>
    </div>

    <script>
        document.querySelector('form').addEventListener('submit', function (e) {
            document.querySelectorAll('input[name$="[enabled]"]').forEach(function (checkbox) {
                if (!checkbox.checked) {
                    const row = checkbox.closest('tr');
                    row.querySelectorAll('input[name$="[order_item_id]"], input[name$="[quantity]"], input[name$="[substitution_reason]"]').forEach(function (input) {
                        input.disabled = true;
                    });
                }
                checkbox.disabled = true;
            });
        });
    </script>
@stop
