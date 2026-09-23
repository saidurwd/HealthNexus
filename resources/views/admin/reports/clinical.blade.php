@extends('adminlte::page')

@section('title', 'Clinical Reports')

@section('content_header')
    <h1>Clinical Reports</h1>
@stop

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="form-inline">
                <label class="mr-2">From</label>
                <input type="date" name="from" value="{{ $from }}" class="form-control mr-3">
                <label class="mr-2">To</label>
                <input type="date" name="to" value="{{ $to }}" class="form-control mr-3">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.reports.clinical.daily-opd') }}" class="btn btn-outline-secondary ml-2">Daily OPD Report</a>
                <a href="{{ route('admin.reports.clinical.provider-workload') }}" class="btn btn-outline-secondary ml-2">Provider Workload</a>
                <a href="{{ route('admin.reports.clinical.diagnosis-statistics') }}" class="btn btn-outline-secondary ml-2">Diagnosis Statistics</a>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-info"><i class="bi bi-journal-medical"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Encounters</span>
                    <span class="info-box-number">{{ $stats['total'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="bi bi-check-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Completed</span>
                    <span class="info-box-number">{{ $stats['completed'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="bi bi-lock"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Locked</span>
                    <span class="info-box-number">{{ $stats['locked'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">By Status</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['by_status'] as $status => $count)
                                <tr>
                                    <td>{{ ucfirst($status) }}</td>
                                    <td>{{ $count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">By Type</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['by_type'] as $type => $count)
                                <tr>
                                    <td>{{ $type }}</td>
                                    <td>{{ $count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop
