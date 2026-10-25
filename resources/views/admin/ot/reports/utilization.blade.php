@extends('layouts.adminlte')

@section('page_title', 'OT Utilization')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Schedule Events</h3></div>
        <div class="card-body">@forelse($cancellations as $type => $total)<div>{{ ucwords(str_replace('_',' ',$type)) }}: {{ $total }}</div>@empty<div>No data.</div>@endforelse</div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Delays by Category</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Category</th><th>Count</th><th>Avg Minutes</th></tr></thead>
                <tbody>
                    @forelse($delays as $d)
                        <tr><td>{{ ucfirst($d->delay_category) }}</td><td>{{ $d->total }}</td><td>{{ round($d->avg_minutes, 1) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-center">No delays recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
