@extends('layouts.adminlte')

@section('page_title', __('Signals'))

@section('page_content')
    @include('admin.watch._flash')
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach(['' => 'All', 'new' => 'New', 'investigating' => 'Being looked into', 'confirmed' => 'Confirmed', 'dismissed' => 'Dismissed'] as $k => $label)
            <a class="btn btn-sm {{ (string) request('status', '') === (string) $k && ! request('unreported') ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.watch.signals', $k ? ['status' => $k] : []) }}">{{ __($label) }}</a>
        @endforeach
        <a class="btn btn-sm {{ request('unreported') ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.watch.signals', ['unreported' => 1]) }}">{{ __('Not yet reported') }}</a>
        @if($canScan)<form method="POST" action="{{ route('admin.watch.scan') }}" class="ms-auto">@csrf<button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i> {{ __('Look now') }}</button></form>@endif
    </div>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0" data-list-count="{{ $rows->total() }}">
        <thead><tr><th>{{ __('Condition') }}</th><th>{{ __('Where') }}</th><th class="text-end">{{ __('Patients') }}</th><th class="text-end">{{ __('Usually') }}</th><th>{{ __('Week of') }}</th><th>{{ __('Why') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
        @forelse($rows as $r)
            <tr><td><a href="{{ route('admin.watch.signals.show', $r->public_id) }}">{{ app()->getLocale() === 'bn' && $r->condition?->name_bn ? $r->condition->name_bn : $r->condition?->name }}</a></td><td>{{ $r->district_id ? ($districts[$r->district_id] ?? '') : __('Whole hospital') }}</td>
                <td class="text-end">{{ $r->cases }}</td><td class="text-end">{{ $r->baseline ?? '—' }}</td><td>{{ $r->week_start->format('d M Y') }}</td><td>{{ __(['spike' => 'Rise above the usual', 'cluster' => 'Cluster in one district', 'first' => 'First cases'][$r->reason] ?? $r->reason) }}</td>
                <td><x-ui.status-badge :status="$r->status" :label="__(\Illuminate\Support\Str::headline($r->status))" />@if($r->status === 'confirmed' && ! $r->reported_at) <span class="badge text-bg-danger">{{ __('Not reported') }}</span>@endif</td></tr>
        @empty<tr><td colspan="7"><x-ui.empty-state :title="__('No signal here.')" icon="bi-bell" /></td></tr>@endforelse
        </tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
