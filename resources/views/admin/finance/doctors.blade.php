@extends('layouts.adminlte')

@section('page_title', __('Doctor revenue and fee share'))

@section('page_content')
    @include('admin.finance._flash')
    @php($m = fn ($v) => number_format((float) $v, 2))
    @include('admin.finance._range', ['from' => $from, 'to' => $to, 'export' => true])
    <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Fee share by doctor') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Doctor') }}</th><th class="text-end">{{ __('Revenue') }}</th><th class="text-end">{{ __('Share') }}</th></tr></thead><tbody>
        @forelse($shares as $s)<tr><td>{{ $s['provider'] }}</td><td class="text-end">{{ $m($s['revenue']) }}</td><td class="text-end">{{ $m($s['amount']) }}</td></tr>@empty<tr><td colspan="3" class="text-muted">{{ __('No doctor has a rule that applies, or no revenue in this range.') }}</td></tr>@endforelse</tbody></table></div>
        @can('finance.feeshare.manage')@if($period)<div class="card-footer"><form method="POST" action="{{ route('admin.finance.doctors.accrue', $period->public_id) }}" onsubmit="return confirm('{{ __('Post the shares of this month to the books?') }}')">@csrf<button class="btn btn-sm btn-primary">{{ __('Post the shares of') }} {{ $period->name }}</button></form></div>@endif @endcan</div>
    <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Revenue by doctor and kind of service') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Doctor') }}</th><th>{{ __('Kind of service') }}</th><th class="text-end">{{ __('Items') }}</th><th class="text-end">{{ __('Revenue') }}</th></tr></thead><tbody>
        @forelse($revenue as $r)<tr><td>{{ $r->provider ?: __('No doctor on the charge') }}</td><td>{{ $r->category }}</td><td class="text-end">{{ $r->items }}</td><td class="text-end">{{ $m($r->revenue) }}</td></tr>@empty<tr><td colspan="4" class="text-muted">{{ __('No revenue in this range.') }}</td></tr>@endforelse</tbody></table></div></div>
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Rules') }}</h3></div><ul class="list-group list-group-flush small">@forelse($rules as $r)<li class="list-group-item">{{ $r->percent }}% — {{ $r->provider_user_id ? ($users[$r->provider_user_id] ?? '#'.$r->provider_user_id) : __('every doctor') }} — {{ $r->category_code ?: __('every kind of service') }}</li>@empty<li class="list-group-item text-muted">{{ __('No rules yet.') }}</li>@endforelse</ul>
        @can('finance.feeshare.manage')<form method="POST" action="{{ route('admin.finance.doctors.rules') }}" class="card-footer row g-2">@csrf
            <div class="col-md-3"><label class="form-label" for="provider_user_id">{{ __('Doctor (user number, empty = everyone)') }}</label><input id="provider_user_id" type="number" name="provider_user_id" class="form-control"></div>
            <div class="col-md-3"><label class="form-label" for="category_code">{{ __('Kind of service (Billing category code, empty = all)') }}</label><input id="category_code" name="category_code" class="form-control"></div>
            <div class="col-md-2"><label class="form-label" for="percent">{{ __('Percent') }}</label><input id="percent" name="percent" class="form-control" required></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary">{{ __('Add rule') }}</button></div></form>@endcan</div>
@stop
