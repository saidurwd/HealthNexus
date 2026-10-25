@extends('layouts.adminlte')

@section('page_title', 'ICU Outcomes Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Critical Events by Type</h3></div>
        <div class="card-body p-0">
            <table class="table table-sm">
                <thead><tr><th>Type</th><th>Count</th></tr></thead>
                <tbody>
                    @foreach($criticalEvents as $type => $count)
                        <tr><td>{{ ucwords(str_replace('_', ' ', $type)) }}</td><td>{{ $count }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
