@extends('layouts.adminlte')

@section('page_title', __('Requests for quotation'))

@section('page_content')
    @include('admin.procurement._flash')
    @can('procurement.rfq.manage')
    <form method="POST" action="{{ route('admin.procurement.rfqs.store') }}" class="card mb-3">@csrf
        <div class="card-header"><h3 class="card-title">{{ __('Ask suppliers to quote for an approved requisition') }}</h3></div>
        <div class="card-body row g-2">
            <div class="col-md-3"><select name="requisition" class="form-select" required>@foreach($approved as $r)<option value="{{ $r->public_id }}">{{ $r->number }} · {{ $r->estimated_total }}</option>@endforeach</select></div>
            <div class="col-md-5"><select name="suppliers[]" class="form-select" multiple size="4" required>@foreach($suppliers as $s)<option value="{{ $s->public_id }}">{{ $s->name }}</option>@endforeach</select><div class="form-text">{{ __('Only qualified suppliers are listed.') }}</div></div>
            <div class="col-md-2"><input type="date" name="closes_on" class="form-control" title="{{ __('Quotes due by') }}"></div><div class="col-md-2"><button class="btn btn-danger w-100">{{ __('Send') }}</button></div>
        </div></form>
    @endcan
    <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>{{ __('Request') }}</th><th>{{ __('Quotes due') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>@forelse($rows as $r)<tr><td><a href="{{ route('admin.procurement.rfqs.show', $r) }}">{{ $r->number }}</a></td><td>{{ $r->closes_on?->toDateString() ?? '—' }}</td><td><x-ui.status-badge :status="$r->status" /></td></tr>
        @empty<tr><td colspan="3" class="text-muted">{{ __('None yet.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
