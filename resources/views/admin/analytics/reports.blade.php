@extends('layouts.adminlte')

@section('page_title', __('Reports'))

@section('page_content')
    @include('admin.analytics._flash')
    <div class="mb-3"><a class="btn btn-primary" href="{{ route('admin.analytics.explore') }}">{{ __('Make a report in the explorer') }}</a> @can('analytics.schedule.manage')<a class="btn btn-outline-secondary" href="{{ route('admin.analytics.schedules') }}">{{ __('Scheduled reports') }}</a>@endcan</div>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Report') }}</th><th>{{ __('Subject') }}</th><th>{{ __('Shared') }}</th></tr></thead><tbody>
        @forelse($rows as $r)<tr><td><a href="{{ route('admin.analytics.reports.show', $r->public_id) }}">{{ $r->name }}</a><div class="small text-muted">{{ $r->description }}</div></td><td>{{ $r->query['fact'] }}</td><td>{{ $r->shared ? __('Yes') : __('No') }}</td></tr>@empty<tr><td colspan="3" class="text-muted">{{ __('No reports yet.') }}</td></tr>@endforelse</tbody></table></div></div>
@stop
