@extends('layouts.adminlte')

@section('page_title', __('Stock counts'))

@section('page_content')
    @include('admin.inventory._flash')
    @can('inventory.count.perform')
    <form method="POST" action="{{ route('admin.inventory.counts.start') }}" class="card mb-3">@csrf<div class="card-body d-flex flex-wrap gap-2 align-items-center">
        <select name="store" class="form-select w-auto" required>@foreach($stores as $s)<option value="{{ $s->public_id }}">{{ $s->name }}</option>@endforeach</select>
        <button class="btn btn-danger">{{ __('Start a full count of this store') }}</button></div></form>
    @endcan
    <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>{{ __('Count') }}</th><th>{{ __('Store') }}</th><th>{{ __('Kind') }}</th><th>{{ __('Status') }}</th><th>{{ __('Started') }}</th></tr></thead>
        <tbody>@forelse($counts as $c)<tr><td><a href="{{ route('admin.inventory.counts.show', $c) }}">{{ $c->number }}</a></td><td>{{ $c->store->name }}</td><td>{{ __(\Illuminate\Support\Str::headline($c->kind)) }}</td><td><x-ui.status-badge :status="$c->status" /></td><td>{{ $c->created_at->format('d M Y H:i') }}</td></tr>
        @empty<tr><td colspan="5" class="text-muted">{{ __('No counts yet.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $counts->links() }}</div></div>
@stop
