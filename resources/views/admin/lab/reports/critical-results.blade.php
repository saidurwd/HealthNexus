@extends('layouts.adminlte')

@section('page_title', 'Critical Result Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Critical Result Report ({{ $from }} to {{ $to }})</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 row g-2">
                <div class="col-md-3"><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
                <div class="col-md-3"><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">Filter</button></div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Test</th><th>Patient</th><th>Value</th><th>Entered At</th></tr></thead>
                <tbody>
                    @forelse($results as $result)
                        <tr>
                            <td>{{ $result->test->name }}</td>
                            <td>{{ $result->orderItem->labOrder->patient?->full_name }}</td>
                            <td>{{ $result->numeric_value ?? $result->qualitative_value ?? $result->text_value }}</td>
                            <td>{{ $result->entered_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No critical results in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $results->links() }}</div>
    </div>
@stop
