@extends('layouts.adminlte')

@section('page_title', __('Trial balance'))

@section('page_content')
    @php($m = fn ($v) => number_format((float) $v, 2))
    @include('admin.finance._range', ['from' => $from, 'to' => $to, 'export' => true, 'extra' => view('admin.finance._cc', ['centers' => $centers, 'cc' => $cc])->render()])
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Code') }}</th><th>{{ __('Account') }}</th><th class="text-end">{{ __('Opening') }}</th><th class="text-end">{{ __('Debit') }}</th><th class="text-end">{{ __('Credit') }}</th><th class="text-end">{{ __('Closing') }}</th></tr></thead><tbody>
        @forelse($rows as $r)<tr><td><a href="{{ route('admin.finance.ledger', ['account' => \App\Models\Finance\Account::query()->whereKey($r->account_id)->value('public_id'), 'from' => $from, 'to' => $to]) }}">{{ $r->code }}</a></td><td>{{ $r->name }}</td><td class="text-end">{{ $m($r->opening) }}</td><td class="text-end">{{ $m($r->debit) }}</td><td class="text-end">{{ $m($r->credit) }}</td><td class="text-end">{{ $m($r->closing) }}</td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('Nothing posted yet.') }}</td></tr>@endforelse</tbody>
        <tfoot><tr class="fw-bold"><td colspan="3">{{ __('Closing balances: debit side / credit side') }}</td><td class="text-end">{{ $m($totals['debit']) }}</td><td class="text-end">{{ $m($totals['credit']) }}</td><td class="{{ $totals['balanced'] ? 'text-success' : 'text-danger' }}">{{ $totals['balanced'] ? __('Balanced') : __('Does not balance') }}</td></tr></tfoot></table></div></div>
@stop
