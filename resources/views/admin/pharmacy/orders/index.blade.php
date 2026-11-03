@extends('layouts.adminlte')

@section('page_title', 'Pharmacy Orders')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Pharmacy Orders (Prescription Queue)') }}</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 row g-2">
                <div class="col-md-4">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('Search by order #...') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-control">
                        <option value="">{{ __('All Statuses') }}</option>
                        @foreach(['pending','under_review','partially_dispensed','fully_dispensed','cancelled','rejected'] as $status)
                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">{{ __('Filter') }}</button></div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>{{ __('Order #') }}</th><th>{{ __('Patient') }}</th><th>{{ __('# Items') }}</th><th>{{ __('Ordered At') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>{{ $order->order_number }}</td>
                            <td>{{ $order->patient?->full_name }}</td>
                            <td>{{ $order->items->count() }}</td>
                            <td>{{ $order->ordered_at?->format('Y-m-d H:i') }}</td>
                            <td><x-ui.status-badge :status="$order->status" /></td>
                            <td><a href="{{ route('admin.pharmacy.orders.show', $order) }}" class="btn btn-xs btn-info">{{ __('View') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">{{ __('No pharmacy orders found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $orders->links() }}</div>
    </div>
@stop
