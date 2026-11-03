@extends('layouts.adminlte')

@section('page_title', __('Supplier payments'))

@section('page_content')
    @include('admin.finance._flash')
    @php($m = fn ($v) => number_format((float) $v, 2))
    <div class="mb-3">@can('finance.payable.manage')<a class="btn btn-primary" href="{{ route('admin.finance.payments.create') }}">{{ __('Pay suppliers') }}</a>@endcan</div>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Number') }}</th><th>{{ __('Date') }}</th><th>{{ __('Supplier') }}</th><th class="text-end">{{ __('Invoices') }}</th><th class="text-end">{{ __('Tax withheld') }}</th><th class="text-end">{{ __('Paid') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
        @forelse($rows as $p)<tr><td><a href="{{ route('admin.finance.payments.show', $p->public_id) }}">{{ $p->number }}</a></td><td>{{ $p->payment_date->format('d M Y') }}</td><td>{{ $suppliers[$p->supplier_id] ?? '' }}</td><td class="text-end">{{ $m($p->gross_amount) }}</td><td class="text-end">{{ $m(bcadd((string) $p->withheld_ait, (string) $p->withheld_vat, 2)) }}</td><td class="text-end">{{ $m($p->net_amount) }}</td><td><x-ui.status-badge :status="$p->status" /></td></tr>@empty<tr><td colspan="7" class="text-muted">{{ __('No payments yet.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
