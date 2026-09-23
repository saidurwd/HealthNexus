@extends('adminlte::page')

@section('title', 'Diagnosis Statistics')

@section('content_header')
    <h1>Diagnosis Statistics</h1>
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
            <h3 class="card-title">Top Diagnoses ({{ $from }} — {{ $to }})</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Diagnosis</th>
                        <th>Coding System</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($diagnoses as $row)
                        <tr>
                            <td>{{ $row->description }}</td>
                            <td>{{ $row->coding_system ?? '—' }}</td>
                            <td>{{ $row->aggregate }}</td>
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
