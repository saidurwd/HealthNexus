@extends('layouts.adminlte')

@section('page_title', 'Bed Occupancy Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Occupancy by Ward</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Ward</th><th>Status</th><th>Count</th></tr></thead>
                <tbody>
                    @forelse($byWard as $wardName => $rows)
                        @foreach($rows as $row)
                            <tr>
                                <td>{{ $wardName }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $row->status)) }}</td>
                                <td>{{ $row->total }}</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="3" class="text-center">No bed data found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
