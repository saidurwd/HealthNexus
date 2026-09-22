@extends('layouts.adminlte')

@section('page_title', 'Revenue Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Revenue by Category</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 d-flex gap-2">
                <input type="date" name="from" value="{{ $from }}" class="form-control" style="max-width:180px;">
                <input type="date" name="to" value="{{ $to }}" class="form-control" style="max-width:180px;">
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Category</th><th>Revenue</th></tr></thead>
                <tbody>
                    @forelse($byCategory as $row)
                        <tr>
                            <td>{{ $row->category }}</td>
                            <td>{{ number_format($row->revenue, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center">No revenue in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
