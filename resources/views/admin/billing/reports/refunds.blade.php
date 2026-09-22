@extends('layouts.adminlte')

@section('page_title', 'Refund Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Refunds</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 d-flex gap-2">
                <input type="date" name="from" value="{{ $from }}" class="form-control" style="max-width:180px;">
                <input type="date" name="to" value="{{ $to }}" class="form-control" style="max-width:180px;">
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Refund #</th><th>Patient</th><th>Amount</th><th>Status</th><th>Requested At</th></tr></thead>
                <tbody>
                    @forelse($refunds as $refund)
                        <tr>
                            <td>{{ $refund->refund_number }}</td>
                            <td>{{ $refund->patient?->full_name }}</td>
                            <td>{{ number_format($refund->amount, 2) }}</td>
                            <td>{{ ucfirst($refund->status) }}</td>
                            <td>{{ $refund->requested_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No refunds in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
