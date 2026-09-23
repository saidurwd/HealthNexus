@extends('layouts.adminlte')

@section('page_title', 'Pharmacy Dashboard')

@section('page_content')
    <div class="row">
        <div class="col-md-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $summary['orders_pending'] }}</h3>
                    <p>Pending Orders</p>
                </div>
                <div class="icon"><i class="bi bi-capsule"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $summary['orders_under_review'] }}</h3>
                    <p>Under Review</p>
                </div>
                <div class="icon"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $summary['near_expiry_batches'] }}</h3>
                    <p>Near-Expiry Batches</p>
                </div>
                <div class="icon"><i class="bi bi-calendar-x"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $summary['unresolved_safety_alerts'] }}</h3>
                    <p>Unresolved Safety Alerts</p>
                </div>
                <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Today's Summary</h3></div>
        <div class="card-body">
            <table class="table table-sm">
                <tr><th>Dispensed Today</th><td>{{ $summary['dispensed_today'] }}</td></tr>
                <tr><th>Quarantined Batches</th><td>{{ $summary['quarantined_batches'] }}</td></tr>
            </table>
        </div>
    </div>
@stop
