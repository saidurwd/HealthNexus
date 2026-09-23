@extends('layouts.adminlte')

@section('page_title', 'Modality Utilization')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Modality Utilization ({{ $from }} to {{ $to }})</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 row g-2">
                <div class="col-md-3"><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
                <div class="col-md-3"><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">Filter</button></div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Modality</th><th>Completed Examinations</th></tr></thead>
                <tbody>
                    @forelse($utilization as $row)
                        <tr><td>{{ $row->modality_name }}</td><td>{{ $row->total }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center">No data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
