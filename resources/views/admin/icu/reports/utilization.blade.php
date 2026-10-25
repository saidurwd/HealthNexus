@extends('layouts.adminlte')

@section('page_title', 'ICU Utilization Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Admissions per Day (last 30 days)</h3></div>
        <div class="card-body p-0">
            <table class="table table-sm">
                <thead><tr><th>Day</th><th>Admissions</th></tr></thead>
                <tbody>
                    @foreach($admissionsPerDay as $row)
                        <tr><td>{{ $row->day }}</td><td>{{ $row->total }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
