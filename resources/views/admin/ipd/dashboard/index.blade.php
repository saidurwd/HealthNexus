@extends('layouts.adminlte')

@section('page_title', 'IPD Dashboard')

@section('page_content')
    <div class="row">
        <div class="col-md-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $summary['current_inpatients'] }}</h3>
                    <p>{{ __('Current Inpatients') }}</p>
                </div>
                <div class="icon"><i class="bi bi-hospital"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $summary['admissions_today'] }}</h3>
                    <p>{{ __('Admissions Today') }}</p>
                </div>
                <div class="icon"><i class="bi bi-box-arrow-in-right"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $summary['discharges_today'] }}</h3>
                    <p>{{ __('Discharges Today') }}</p>
                </div>
                <div class="icon"><i class="bi bi-box-arrow-right"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $summary['delayed_discharges'] }}</h3>
                    <p>{{ __('Delayed Discharges') }}</p>
                </div>
                <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Today\'s Activity') }}</h3></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><th>{{ __('Transfers Today') }}</th><td>{{ $summary['transfers_today'] }}</td></tr>
                        <tr><th>{{ __('Pending Admission Requests') }}</th><td>{{ $summary['pending_admission_requests'] }}</td></tr>
                        <tr><th>{{ __('Pending Transfers') }}</th><td>{{ $summary['pending_transfers'] }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Bed Statistics') }}</h3></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><th>{{ __('Total Beds') }}</th><td>{{ $summary['total_beds'] }}</td></tr>
                        <tr><th>{{ __('Available') }}</th><td>{{ $summary['available_beds'] }}</td></tr>
                        <tr><th>{{ __('Occupied') }}</th><td>{{ $summary['occupied_beds'] }}</td></tr>
                        <tr><th>{{ __('Reserved') }}</th><td>{{ $summary['reserved_beds'] }}</td></tr>
                        <tr><th>{{ __('Cleaning') }}</th><td>{{ $summary['cleaning_beds'] }}</td></tr>
                        <tr><th>{{ __('Maintenance') }}</th><td>{{ $summary['maintenance_beds'] }}</td></tr>
                        <tr><th>{{ __('Isolation') }}</th><td>{{ $summary['isolation_beds'] }}</td></tr>
                        <tr><th>{{ __('Occupancy Rate') }}</th><td>{{ $summary['occupancy_rate'] }}%</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop
