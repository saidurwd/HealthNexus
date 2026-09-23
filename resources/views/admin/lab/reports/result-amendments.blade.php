@extends('layouts.adminlte')

@section('page_title', 'Result Amendment Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Result Amendment Report ({{ $from }} to {{ $to }})</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 row g-2">
                <div class="col-md-3"><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
                <div class="col-md-3"><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">Filter</button></div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Test</th><th>Patient</th><th>Amended By</th><th>Reason</th><th>At</th></tr></thead>
                <tbody>
                    @forelse($amendments as $amendment)
                        <tr>
                            <td>{{ $amendment->test->name }}</td>
                            <td>{{ $amendment->orderItem->labOrder->patient?->full_name }}</td>
                            <td>{{ $amendment->amendedBy?->name }}</td>
                            <td>{{ $amendment->amendment_reason }}</td>
                            <td>{{ $amendment->entered_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No amendments in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $amendments->links() }}</div>
    </div>
@stop
