@extends('layouts.adminlte')

@section('page_title', 'Laboratory Dashboard')

@section('page_content')
    <div class="row">
        <div class="col-md-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $summary['orders_today'] }}</h3>
                    <p>Orders Today</p>
                </div>
                <div class="icon"><i class="bi bi-clipboard2-pulse"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $summary['specimens_collected_today'] }}</h3>
                    <p>Specimens Collected Today</p>
                </div>
                <div class="icon"><i class="bi bi-droplet"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $summary['specimens_pending'] }}</h3>
                    <p>Specimens Pending</p>
                </div>
                <div class="icon"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $summary['critical_unacknowledged'] }}</h3>
                    <p>Unacknowledged Critical Results</p>
                </div>
                <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Today's Summary</h3></div>
        <div class="card-body">
            <table class="table table-sm">
                <tr><th>Specimens Rejected Today</th><td>{{ $summary['specimens_rejected_today'] }}</td></tr>
                <tr><th>Orders Awaiting Validation</th><td>{{ $summary['orders_awaiting_validation'] }}</td></tr>
                <tr><th>Reports Completed Today</th><td>{{ $summary['reports_completed_today'] }}</td></tr>
            </table>
        </div>
    </div>
@stop
