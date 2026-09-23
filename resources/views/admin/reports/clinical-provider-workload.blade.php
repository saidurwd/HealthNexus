@extends('adminlte::page')

@section('title', 'Provider Workload Report')

@section('content_header')
    <h1>Provider Workload Report</h1>
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
                <a href="{{ route('admin.reports.clinical') }}" class="btn btn-outline-secondary ml-2">Back to Clinical Reports</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Encounters per Provider ({{ $from }} — {{ $to }})</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Provider</th>
                        <th>Total Encounters</th>
                        <th>Completed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($workload as $row)
                        <tr>
                            <td>{{ $row->provider?->name ?? $row->provider_id }}</td>
                            <td>{{ $row->total }}</td>
                            <td>{{ $row->completed }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">No data for this range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
