@extends('layouts.adminlte')

@section('page_title', __('Analytics'))

@section('page_content')
    @include('admin.analytics._flash')
    <div class="row g-3 mb-3">
        @forelse($dashboards as $d)<div class="col-md-4 col-xl-3"><a href="{{ route('admin.analytics.dashboards.show', $d->public_id) }}" class="card h-100 text-decoration-none"><div class="card-body"><div class="fw-bold">{{ __($d->name) }}</div><div class="small text-muted">{{ $d->is_system ? __('Standard dashboard') : __('Our own dashboard') }} · {{ count($d->widgets) }} {{ __('widgets') }}</div></div></a></div>@empty<div class="col-12 text-muted">{{ __('No dashboards yet. Run the analytics install command.') }}</div>@endforelse
        @can('analytics.dashboard.manage')<div class="col-md-4 col-xl-3"><a href="{{ route('admin.analytics.dashboards.create') }}" class="card h-100 text-decoration-none border-dashed"><div class="card-body text-center text-muted"><i class="bi bi-plus-circle fs-3"></i><div>{{ __('Make a dashboard') }}</div></div></a></div>@endcan
    </div>
    <div class="row g-3">
        @foreach([['analytics.kpi.view', 'admin.analytics.kpis', 'KPIs and targets', 'bi-speedometer'], ['analytics.query.run', 'admin.analytics.explore', 'Explore the figures', 'bi-search'], ['analytics.report.view', 'admin.analytics.reports', 'Reports', 'bi-file-earmark-text'], ['analytics.question.ask', 'admin.analytics.ask', 'Ask a question', 'bi-chat-dots'],
            ['analytics.forecast.view', 'admin.analytics.forecast', 'Planning forecasts', 'bi-graph-up-arrow'], ['analytics.benchmark.view', 'admin.analytics.benchmark', 'Compare with other hospitals', 'bi-people'], ['analytics.national.view', 'admin.analytics.national', 'National reporting pack', 'bi-globe2'], ['analytics.quality.view', 'admin.analytics.quality', 'Data quality', 'bi-shield-check'], ['analytics.load.view', 'admin.analytics.loads', 'Warehouse loads', 'bi-database']] as [$perm, $route, $label, $icon])
            @can($perm)<div class="col-6 col-md-4 col-xl-3"><a class="btn btn-outline-secondary w-100 text-start" href="{{ route($route) }}"><i class="bi {{ $icon }}"></i> {{ __($label) }}</a></div>@endcan
        @endforeach
    </div>
@stop
