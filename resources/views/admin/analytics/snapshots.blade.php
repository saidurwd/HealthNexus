@extends('layouts.adminlte')

@section('page_title', __('Kept dashboard snapshots'))

@section('page_content')
    @include('admin.analytics._flash')
    <p class="text-muted">{{ __('A snapshot keeps a dashboard as it looked for a period, so the figures can be compared and printed later. The month-end pack is made by the system on the 1st of each month.') }}</p>
    @if($months->isNotEmpty())
        <div class="d-flex flex-wrap gap-2 mb-3">@foreach($months->take(12) as $m)<a class="btn btn-sm btn-outline-primary" href="{{ route('admin.analytics.pack', $m) }}"><i class="bi bi-printer"></i> {{ __('Month-end pack') }} {{ \Carbon\CarbonImmutable::parse($m.'-01')->format('M Y') }}</a>@endforeach</div>
    @endif
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>{{ __('Dashboard') }}</th><th>{{ __('Period') }}</th><th>{{ __('Kind') }}</th><th>{{ __('Kept on') }}</th></tr></thead>
        <tbody>
        @forelse($rows as $r)
            <tr><td><a href="{{ route('admin.analytics.snapshots.show', $r->public_id) }}">{{ __($r->dashboard_name) }}</a></td><td>{{ $r->period_from->format('d M Y') }} – {{ $r->period_to->format('d M Y') }}</td><td>{{ $r->kind === 'monthly' ? __('Month-end pack') : __('Kept by hand') }}</td><td>{{ $r->created_at->setTimezone('Asia/Dhaka')->format('d M Y, H:i') }}</td></tr>
        @empty
            <tr><td colspan="4"><x-ui.empty-state :title="__('No snapshot has been kept yet.')" :hint="__('Open a dashboard and press “Keep a snapshot of this view”.')" icon="bi-collection" /></td></tr>
        @endforelse
        </tbody></table></div></div>
    <div class="mt-3">{{ $rows->links() }}</div>
@stop
