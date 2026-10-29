@extends('layouts.adminlte')

@section('page_title', __('Fiscal years and periods'))

@section('page_content')
    @include('admin.finance._flash')
    @forelse($years as $y)
        <div class="card mb-3"><div class="card-header d-flex justify-content-between"><h3 class="card-title">{{ $y->name }} <small class="text-muted">{{ $y->starts_on->format('d M Y') }} – {{ $y->ends_on->format('d M Y') }}</small></h3><x-ui.status-badge :status="$y->status" /></div>
            <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Period') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead><tbody>
                @foreach($y->periods as $p)<tr><td>{{ $p->name }}</td><td><x-ui.status-badge :status="$p->status" /> <small class="text-muted">{{ $p->lock_note }}</small></td><td>
                    @can('finance.period.manage')@if($p->status === 'open')<form method="POST" action="{{ route('admin.finance.periods.lock', $p->public_id) }}" class="d-flex gap-1">@csrf<input type="hidden" name="action" value="lock"><input name="note" class="form-control form-control-sm" placeholder="{{ __('Note (optional)') }}" aria-label="{{ __('Note') }}"><button class="btn btn-sm btn-outline-warning">{{ __('Lock') }}</button></form>
                    @elseif($p->status === 'locked')<form method="POST" action="{{ route('admin.finance.periods.lock', $p->public_id) }}" class="d-flex gap-1">@csrf<input type="hidden" name="action" value="unlock"><input name="note" class="form-control form-control-sm" placeholder="{{ __('Why is it opened again?') }}" required aria-label="{{ __('Reason') }}"><button class="btn btn-sm btn-outline-primary">{{ __('Open again') }}</button></form>@endif @endcan</td></tr>@endforeach</tbody></table></div>
            @if($y->status === 'open')<div class="card-footer small">@if($blockers[$y->id])<div class="text-muted">{{ __('The year cannot be closed yet:') }}</div><ul class="mb-0">@foreach($blockers[$y->id] as $b)<li>{{ $b }}</li>@endforeach</ul>
                @else @can('finance.year.close')<form method="POST" action="{{ route('admin.finance.years.close', $y->public_id) }}" onsubmit="return confirm('{{ __('Close this year? This sweeps the profit or loss into retained earnings and cannot be undone.') }}')">@csrf<button class="btn btn-sm btn-danger">{{ __('Close the year') }}</button></form>@endcan @endif</div>@endif</div>
    @empty<div class="alert alert-info">{{ __('No fiscal year yet.') }}</div>@endforelse
    @can('finance.period.manage')<form method="POST" action="{{ route('admin.finance.years.store') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Create a fiscal year') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-3"><label class="form-label" for="start_year">{{ __('Year it starts in') }}</label><input id="start_year" name="start_year" type="number" class="form-control" value="{{ now()->year }}" required></div>
        <div class="col-md-3"><label class="form-label" for="first_month">{{ __('First month (1 = January)') }}</label><input id="first_month" name="first_month" type="number" min="1" max="12" class="form-control" value="1" required></div>
        <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary">{{ __('Create') }}</button></div></div></form>@endcan
@stop
