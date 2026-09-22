@extends('layouts.adminlte')

@section('page_title', 'Cashier Reconciliation')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Reconciliation — {{ $session->user?->name }}</h3></div>
        <div class="card-body">
            <table class="table table-sm">
                <tr><th>Opening Balance</th><td>{{ number_format($session->opening_balance, 2) }}</td></tr>
                <tr><th>Cash Collections</th><td>{{ number_format($reconciliation['expected_collections'], 2) }}</td></tr>
                <tr><th>Cash Refunds</th><td>{{ number_format($reconciliation['expected_refunds'], 2) }}</td></tr>
                <tr><th>Expected Closing</th><td>{{ number_format($reconciliation['expected_closing'], 2) }}</td></tr>
                <tr><th>Actual Closing</th><td>{{ number_format($session->actual_closing, 2) }}</td></tr>
                <tr>
                    <th>Variance</th>
                    <td class="{{ (float) $session->variance != 0 ? 'text-danger fw-bold' : 'text-success' }}">
                        {{ number_format($session->variance, 2) }}
                    </td>
                </tr>
                <tr><th>Closing Notes</th><td>{{ $session->closing_notes ?? '-' }}</td></tr>
            </table>
        </div>
    </div>
@stop
