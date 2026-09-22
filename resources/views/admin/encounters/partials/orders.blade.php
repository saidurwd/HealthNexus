<div class="tab-pane fade" id="orders" role="tabpanel">
    <div class="card card-secondary card-outline">
        <div class="card-header">
            <h3 class="card-title">Clinical Orders</h3>
        </div>
        <div class="card-body">
            @if($encounter->orders->isNotEmpty())
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Type</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Ordered At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($encounter->orders as $order)
                            <tr>
                                <td>{{ $order->order_number }}</td>
                                <td>{{ $order->order_type }}</td>
                                <td>{{ $order->priority }}</td>
                                <td>{{ $order->status }}</td>
                                <td>{{ $order->ordered_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted">No clinical orders.</p>
            @endif

            @if(!$encounter->locked_at)
                <form method="POST" action="{{ route('admin.encounters.orders.store', $encounter) }}" class="mt-3">
                    @csrf
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Order Type</label>
                                <select name="order_type" class="form-control" required>
                                    <option value="laboratory">Laboratory</option>
                                    <option value="radiology">Radiology</option>
                                    <option value="procedure">Procedure</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Priority</label>
                                <select name="priority" class="form-control">
                                    <option value="routine">Routine</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="stat">STAT</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea name="notes" class="form-control" rows="1"></textarea>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2">Create Order</button>
                </form>
            @endif
        </div>
    </div>
</div>
