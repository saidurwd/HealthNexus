@extends('layouts.adminlte')

@section('page_title', 'Sample Rejection Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Sample Rejection Report ({{ $from }} to {{ $to }})</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 row g-2">
                <div class="col-md-3"><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
                <div class="col-md-3"><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">Filter</button></div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Reason</th><th>Total</th></tr></thead>
                <tbody>
                    @forelse($rejections as $row)
                        <tr>
                            <td>{{ config('laboratory.rejection_reasons')[$row->rejection_reason] ?? $row->rejection_reason }}</td>
                            <td>{{ $row->total }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center">No rejections in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
