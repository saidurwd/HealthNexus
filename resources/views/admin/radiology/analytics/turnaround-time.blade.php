@extends('layouts.adminlte')

@section('page_title', 'Turnaround Time')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Examination Turnaround Time ({{ $from }} to {{ $to }})</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 row g-2">
                <div class="col-md-3"><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
                <div class="col-md-3"><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">Filter</button></div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Procedure</th><th>Modality</th><th>Started</th><th>Completed</th><th>TAT (minutes)</th></tr></thead>
                <tbody>
                    @forelse($examinations as $row)
                        <tr>
                            <td>{{ $row['examination']->orderItem->procedure?->name }}</td>
                            <td>{{ $row['examination']->modality?->name }}</td>
                            <td>{{ $row['examination']->started_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $row['examination']->completed_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $row['tat_minutes'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
