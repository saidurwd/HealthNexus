@extends('layouts.adminlte')

@section('page_title', 'Collection Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Today's Collection by Method</h3></div>
        <div class="card-body">
            <table class="table table-sm">
                <tr><th>Open Cashier Sessions</th><td>{{ $summary['open_sessions'] }}</td></tr>
                <tr><th>Cash</th><td>{{ number_format($summary['cash_collection'], 2) }}</td></tr>
                <tr><th>Card</th><td>{{ number_format($summary['card_collection'], 2) }}</td></tr>
                <tr><th>Mobile Financial Service</th><td>{{ number_format($summary['mfs_collection'], 2) }}</td></tr>
                <tr><th>Bank Transfer</th><td>{{ number_format($summary['bank_collection'], 2) }}</td></tr>
            </table>
        </div>
    </div>
@stop
