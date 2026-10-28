@extends('layouts.adminlte')

@section('page_title', __('Visit ratings'))

@section('page_content')
    @include('admin.crm._flash')
    <div class="row g-3 mb-3">@foreach([[__('Ratings, last 90 days'), $summary['count']], [__('Average (1 to 5)'), $summary['average'] ?? '—'], [__('Net promoter score'), $summary['nps'] ?? '—'], [__('Low ratings (1 or 2)'), $summary['low']]] as [$l, $v])<div class="col-6 col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ $l }}</div><div class="fs-4 fw-semibold">{{ $v }}</div></div></div></div>@endforeach</div>
    <form method="GET" class="d-flex gap-2 mb-3"><div class="form-check align-self-center"><input type="checkbox" class="form-check-input" name="low" value="1" id="low" @checked(request()->boolean('low')) onchange="this.form.submit()"><label for="low" class="form-check-label">{{ __('Low ratings only') }}</label></div><select name="status" class="form-select w-auto" onchange="this.form.submit()"><option value="">{{ __('All') }}</option><option value="new" @selected(request('status') === 'new')>{{ __('Not looked at') }}</option><option value="reviewed" @selected(request('status') === 'reviewed')>{{ __('Looked at') }}</option></select></form>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('When') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Doctor') }}</th><th>{{ __('Rating') }}</th><th>{{ __('Would recommend (0 to 10)') }}</th><th>{{ __('Comment') }}</th><th></th></tr></thead>
        <tbody>@forelse($rows as $f)<tr class="{{ $f->rating <= 2 ? 'table-warning' : '' }}"><td>{{ $f->created_at->format('d M') }}</td><td>{{ $f->patient?->first_name }} {{ $f->patient?->last_name }}</td><td>{{ $f->provider?->name }}</td><td>{{ str_repeat('★', $f->rating) }}{{ str_repeat('☆', 5 - $f->rating) }}</td><td>{{ $f->nps }}</td><td class="small">{{ $f->comment }}</td><td>@can('crm.feedback.manage')@if($f->status === 'new')<form method="POST" action="{{ route('admin.crm.feedback.review', $f->public_id) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ __('Looked at') }}</button></form>@endif @endcan</td></tr>
        @empty<tr><td colspan="7" class="text-muted">{{ __('No ratings.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
