@extends('layouts.adminlte')

@section('page_title', 'Nursing Workload')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Patients per Nurse</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Nurse</th><th>Active Patients</th></tr></thead>
                <tbody>
                    @forelse($rows as $row)<tr><td>{{ $row->nurse?->name }}</td><td>{{ $row->patients }}</td></tr>@empty<tr><td colspan="2" class="text-center">No assignments.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Tasks by Status</h3></div>
        <div class="card-body">@foreach($taskStats as $status => $total)<div>{{ ucfirst($status) }}: {{ $total }}</div>@endforeach</div>
    </div>
@stop
