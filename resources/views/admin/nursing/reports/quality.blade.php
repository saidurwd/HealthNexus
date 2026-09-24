@extends('layouts.adminlte')

@section('page_title', 'Nursing Quality Indicators')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Risk Assessments</h3></div>
        <div class="card-body">
            @forelse($riskCounts as $r)<div>{{ ucfirst(str_replace('_',' ',$r->risk_type)) }} — {{ $r->risk_level ?? 'unrated' }}: {{ $r->total }}</div>@empty<div>No data.</div>@endforelse
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Escalations</h3></div>
        <div class="card-body">Total: {{ $escalations->total ?? 0 }}, open: {{ $escalations->open_count ?? 0 }}</div>
    </div>
@stop
