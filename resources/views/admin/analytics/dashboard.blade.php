@extends('layouts.adminlte')

@section('page_title', __($d->name))

@section('page_content')
    @include('admin.analytics._flash')
    <x-ui.dashboard :question="__($d->name)" :audience="$d->is_system ? __('A standard dashboard for this role') : __('A dashboard made by your hospital')" :period="$period" :periods="['7d', '30d', '90d', 'month', 'last_month', 'custom']" :as-of="$asOf">
        <div class="d-flex flex-wrap gap-2 mb-3 d-print-none">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> {{ __('Print (A4)') }}</button>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.analytics.snapshots') }}"><i class="bi bi-collection"></i> {{ __('Kept snapshots') }}</a>
            @if($canSnapshot)
                <form method="POST" action="{{ route('admin.analytics.dashboards.snapshot', $d->public_id) }}" class="d-inline">@csrf
                    @foreach($period->query() as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-camera"></i> {{ __('Keep a snapshot of this view') }}</button></form>
            @endif
        </div>
        <div class="row g-3">
            @foreach($widgets as $w)
                <div class="{{ $w['type'] === 'kpi' ? 'col-6 col-md-4 col-xl-3' : 'col-lg-6' }}">@include('admin.analytics._widget', ['w' => $w])</div>
            @endforeach
        </div>
    </x-ui.dashboard>
    @if(! $d->is_system)@can('analytics.dashboard.manage')<form method="POST" action="{{ route('admin.analytics.dashboards.delete', $d->public_id) }}" class="mt-3 d-print-none" onsubmit="return confirm('{{ __('Delete this dashboard?') }}')">@csrf<button class="btn btn-sm btn-outline-danger">{{ __('Delete this dashboard') }}</button></form>@endcan @endif
@stop
