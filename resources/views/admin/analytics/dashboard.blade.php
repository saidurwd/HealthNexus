@extends('layouts.adminlte')

@section('page_title', __($d->name))

@section('page_content')
    @include('admin.analytics._flash')
    @include('admin.analytics._range', ['from' => $from, 'to' => $to])
    <div class="row g-3">
        @foreach($widgets as $w)
            <div class="{{ $w['type'] === 'kpi' ? 'col-6 col-md-4 col-xl-3' : 'col-lg-6' }}">@include('admin.analytics._widget', ['w' => $w])</div>
        @endforeach
    </div>
    @if(! $d->is_system)@can('analytics.dashboard.manage')<form method="POST" action="{{ route('admin.analytics.dashboards.delete', $d->public_id) }}" class="mt-3" onsubmit="return confirm('{{ __('Delete this dashboard?') }}')">@csrf<button class="btn btn-sm btn-outline-danger">{{ __('Delete this dashboard') }}</button></form>@endcan @endif
@stop
