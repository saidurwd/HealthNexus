@extends('layouts.adminlte')

@section('page_title', __('Fixed assets'))

@section('page_content')
    @include('admin.finance._flash')
    @php($m = fn ($v) => number_format((float) $v, 2))
    <div class="d-flex gap-2 mb-3 align-items-center">
        @can('finance.asset.manage')<form method="POST" action="{{ route('admin.finance.assets.depreciate') }}" class="d-flex gap-1">@csrf<select name="period_id" class="form-select" aria-label="{{ __('Month') }}">@foreach($periods as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select><button class="btn btn-primary">{{ __('Charge depreciation') }}</button></form>@endcan
        @if(auth()->user()->can('finance.report.export'))<a class="btn btn-outline-secondary ms-auto" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"><i class="bi bi-download"></i> {{ __('Download') }}</a>@endif</div>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Tag') }}</th><th>{{ __('Name') }}</th><th>{{ __('Acquired') }}</th><th class="text-end">{{ __('Cost') }}</th><th class="text-end">{{ __('Depreciated so far') }}</th><th class="text-end">{{ __('Book value') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead><tbody>
        @forelse($rows as $a)<tr><td>{{ $a->tag }}</td><td>{{ $a->name }}</td><td>{{ $a->acquired_on->format('d M Y') }}</td><td class="text-end">{{ $m($a->cost) }}</td><td class="text-end">{{ $m($a->accumulated) }}</td><td class="text-end">{{ $m($svc->bookValue($a)) }}</td><td><x-ui.status-badge :status="$a->status" /></td>
            <td>@can('finance.asset.manage')@if($a->status !== 'disposed')<form method="POST" action="{{ route('admin.finance.assets.dispose', $a->public_id) }}" class="d-flex gap-1" onsubmit="return confirm('{{ __('Dispose of this asset?') }}')">@csrf<input type="date" name="date" class="form-control form-control-sm" required value="{{ now()->setTimezone('Asia/Dhaka')->toDateString() }}" aria-label="{{ __('Date') }}"><input name="proceeds" class="form-control form-control-sm" style="width:6rem" placeholder="{{ __('Sold for') }}" value="0" aria-label="{{ __('Sold for') }}"><select name="account" class="form-select form-select-sm" aria-label="{{ __('Money went to') }}" style="width:9rem">@foreach($accounts as $x)<option value="{{ $x->code }}">{{ $x->code }} {{ $x->name }}</option>@endforeach</select><button class="btn btn-sm btn-outline-danger">{{ __('Dispose') }}</button></form>@endif @endcan</td></tr>
        @empty<tr><td colspan="8" class="text-muted">{{ __('No assets yet.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
    @can('finance.asset.manage')<form method="POST" action="{{ route('admin.finance.assets.store') }}" class="card mt-3"><div class="card-header"><h3 class="card-title">{{ __('Register an asset') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-2"><label class="form-label" for="tag">{{ __('Tag') }}</label><input id="tag" name="tag" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label" for="name">{{ __('Name') }}</label><input id="name" name="name" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label" for="acquired_on">{{ __('Acquired on') }}</label><input type="date" id="acquired_on" name="acquired_on" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label" for="cost">{{ __('Cost') }}</label><input id="cost" name="cost" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label" for="salvage_value">{{ __('Scrap value') }}</label><input id="salvage_value" name="salvage_value" class="form-control" value="0"></div>
        <div class="col-md-2"><label class="form-label" for="life_months">{{ __('Life (months)') }}</label><input id="life_months" type="number" name="life_months" class="form-control"></div>
        <div class="col-md-3"><label class="form-label" for="cost_center_id">{{ __('Cost centre') }}</label><select id="cost_center_id" name="cost_center_id" class="form-select"><option value="">—</option>@foreach($centers as $c)<option value="{{ $c->id }}">{{ $c->code }} {{ $c->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label" for="credit_account">{{ __('Book the purchase against (optional)') }}</label><select id="credit_account" name="credit_account" class="form-select"><option value="">—</option>@foreach($accounts as $x)<option value="{{ $x->code }}">{{ $x->code }} {{ $x->name }}</option>@endforeach</select></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary">{{ __('Register') }}</button></div></div></form>@endcan
@stop
