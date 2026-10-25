@extends('layouts.adminlte')

@section('page_title', 'Billing Dashboard')

@section('page_content')
    <div class="row">
        <div class="col-md-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ number_format($todaySummary['total_billed'], 2) }}</h3>
                    <p>{{ __('Total Billed Today') }}</p>
                </div>
                <div class="icon"><i class="bi bi-receipt"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($todaySummary['total_collected'], 2) }}</h3>
                    <p>{{ __('Total Collected Today') }}</p>
                </div>
                <div class="icon"><i class="bi bi-cash-coin"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($todaySummary['total_due'], 2) }}</h3>
                    <p>{{ __('Total Outstanding') }}</p>
                </div>
                <div class="icon"><i class="bi bi-exclamation-circle"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format($todaySummary['total_refunds'], 2) }}</h3>
                    <p>{{ __('Total Refunds Today') }}</p>
                </div>
                <div class="icon"><i class="bi bi-arrow-counterclockwise"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Today\'s Summary') }}</h3></div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th>{{ __('Total Billed') }}</th><td>{{ number_format($todaySummary['total_billed'], 2) }}</td></tr>
                        <tr><th>{{ __('Total Collected') }}</th><td>{{ number_format($todaySummary['total_collected'], 2) }}</td></tr>
                        <tr><th>{{ __('Total Due') }}</th><td>{{ number_format($todaySummary['total_due'], 2) }}</td></tr>
                        <tr><th>{{ __('Total Discounts') }}</th><td>{{ number_format($todaySummary['total_discounts'], 2) }}</td></tr>
                        <tr><th>{{ __('Invoices Today') }}</th><td>{{ $todaySummary['invoice_count'] }}</td></tr>
                        <tr><th>{{ __('Payments Today') }}</th><td>{{ $todaySummary['payment_count'] }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Cashier') }}</h3></div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th>{{ __('Open Sessions') }}</th><td>{{ $cashierSummary['open_sessions'] }}</td></tr>
                        <tr><th>{{ __('Cash Collection') }}</th><td>{{ number_format($cashierSummary['cash_collection'], 2) }}</td></tr>
                        <tr><th>{{ __('Card Collection') }}</th><td>{{ number_format($cashierSummary['card_collection'], 2) }}</td></tr>
                        <tr><th>{{ __('MFS Collection') }}</th><td>{{ number_format($cashierSummary['mfs_collection'], 2) }}</td></tr>
                        <tr><th>{{ __('Bank Collection') }}</th><td>{{ number_format($cashierSummary['bank_collection'], 2) }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop
