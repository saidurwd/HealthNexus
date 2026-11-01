@extends('layouts.adminlte')

@section('page_title', __('Documents'))

@section('page_content')
    @include('admin.quality._flash')
    <form class="row g-2 mb-3" method="GET"><div class="col-md-3"><input name="q" class="form-control form-control-sm" placeholder="{{ __('Search code or title') }}" value="{{ request('q') }}"></div><div class="col-md-3"><select name="category" class="form-select form-select-sm"><option value="">{{ __('Any kind') }}</option>@foreach($categories as $c)<option value="{{ $c }}" @selected($category === $c)>{{ __(\Illuminate\Support\Str::headline($c)) }}</option>@endforeach</select></div>
        <div class="col-md-2"><button class="btn btn-sm btn-primary">{{ __('Filter') }}</button></div>@if($canManage)<div class="col-md-4 text-end"><a class="btn btn-sm btn-success" href="{{ route('admin.quality.documents.create') }}">{{ __('New document') }}</a></div>@endif</form>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Code') }}</th><th>{{ __('Title') }}</th><th>{{ __('Kind') }}</th><th>{{ __('Version') }}</th><th>{{ __('In force since') }}</th><th>{{ __('Review due') }}</th><th>{{ __('Owner') }}</th></tr></thead><tbody>
        @forelse($docs as $d)<tr><td><a href="{{ route('admin.quality.documents.show', $d->public_id) }}">{{ $d->code }}</a></td><td>{{ $d->title }} @if($d->status === 'retired')<x-ui.status-badge status="retired" />@endif</td><td>{{ __(\Illuminate\Support\Str::headline($d->category)) }}</td><td>{{ $d->current ? 'v'.$d->current->version : '—' }}</td><td>{{ $d->current?->effective_on?->format('d M Y') ?? '—' }}</td>
            <td>{{ $d->current?->review_due_on?->format('d M Y') ?? '—' }} @if($d->current?->review_due_on?->lt(today()))<x-ui.status-badge status="overdue" />@endif</td><td>{{ $d->owner?->name }}</td></tr>
        @empty<tr><td colspan="7" class="text-muted">{{ __('No documents.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $docs->links() }}</div></div>
@stop
