@extends('layouts.adminlte')

@section('page_title', 'Charges')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Charges</h3>
        </div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <select name="status" class="form-control" style="max-width: 200px; display: inline-block;" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach(['pending','billed','cancelled','refunded','adjusted'] as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </form>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Net Amount</th>
                        <th>Status</th>
                        <th>Charged At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($charges as $charge)
                        <tr>
                            <td>{{ $charge->patient?->full_name }}</td>
                            <td>{{ $charge->billingItem?->name }}</td>
                            <td>{{ $charge->quantity }}</td>
                            <td>{{ number_format($charge->net_amount, 2) }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($charge->status) }}</span></td>
                            <td>{{ $charge->charged_at?->format('Y-m-d H:i') }}</td>
                            <td><a href="{{ route('admin.billing.charges.show', $charge) }}" class="btn btn-xs btn-info">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No charges found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $charges->links() }}</div>
    </div>
@stop
