@extends('layouts.adminlte')

@section('page_title', 'Radiology Dashboard')

@section('page_content')
    <div class="row">
        <div class="col-md-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $summary['orders_today'] }}</h3>
                    <p>Orders Today</p>
                </div>
                <div class="icon"><i class="bi bi-clipboard-check"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $summary['waiting_patients'] }}</h3>
                    <p>Waiting Patients</p>
                </div>
                <div class="icon"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $summary['awaiting_reporting'] }}</h3>
                    <p>Awaiting Reporting</p>
                </div>
                <div class="icon"><i class="bi bi-file-earmark-medical"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $summary['critical_unacknowledged'] }}</h3>
                    <p>Unacknowledged Critical Findings</p>
                </div>
                <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Today's Summary</h3></div>
        <div class="card-body">
            <table class="table table-sm">
                <tr><th>Scheduled Today</th><td>{{ $summary['scheduled_today'] }}</td></tr>
                <tr><th>In Progress</th><td>{{ $summary['in_progress'] }}</td></tr>
                <tr><th>Completed Today</th><td>{{ $summary['completed_today'] }}</td></tr>
                <tr><th>Urgent/STAT Studies</th><td>{{ $summary['urgent_studies'] }}</td></tr>
                <tr><th>Reports Pending Approval</th><td>{{ $summary['reports_pending_approval'] }}</td></tr>
                <tr><th>Reports Completed Today</th><td>{{ $summary['reports_completed_today'] }}</td></tr>
            </table>
        </div>
    </div>
@stop
