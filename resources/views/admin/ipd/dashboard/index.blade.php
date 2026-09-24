@extends('layouts.adminlte')

@section('page_title', 'IPD Dashboard')

@section('page_content')
    <div class="row">
        <div class="col-md-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $summary['current_inpatients'] }}</h3>
                    <p>Current Inpatients</p>
                </div>
                <div class="icon"><i class="bi bi-hospital"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $summary['admissions_today'] }}</h3>
                    <p>Admissions Today</p>
                </div>
                <div class="icon"><i class="bi bi-box-arrow-in-right"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $summary['discharges_today'] }}</h3>
                    <p>Discharges Today</p>
                </div>
                <div class="icon"><i class="bi bi-box-arrow-right"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $summary['delayed_discharges'] }}</h3>
                    <p>Delayed Discharges</p>
                </div>
                <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Today's Activity</h3></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><th>Transfers Today</th><td>{{ $summary['transfers_today'] }}</td></tr>
                        <tr><th>Pending Admission Requests</th><td>{{ $summary['pending_admission_requests'] }}</td></tr>
                        <tr><th>Pending Transfers</th><td>{{ $summary['pending_transfers'] }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Bed Statistics</h3></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><th>Total Beds</th><td>{{ $summary['total_beds'] }}</td></tr>
                        <tr><th>Available</th><td>{{ $summary['available_beds'] }}</td></tr>
                        <tr><th>Occupied</th><td>{{ $summary['occupied_beds'] }}</td></tr>
                        <tr><th>Reserved</th><td>{{ $summary['reserved_beds'] }}</td></tr>
                        <tr><th>Cleaning</th><td>{{ $summary['cleaning_beds'] }}</td></tr>
                        <tr><th>Maintenance</th><td>{{ $summary['maintenance_beds'] }}</td></tr>
                        <tr><th>Isolation</th><td>{{ $summary['isolation_beds'] }}</td></tr>
                        <tr><th>Occupancy Rate</th><td>{{ $summary['occupancy_rate'] }}%</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop
