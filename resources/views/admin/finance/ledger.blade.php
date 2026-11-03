@extends('layouts.adminlte')

@section('page_title', $account->code.' '.$account->name)

@section('page_content')
    @php($m = fn ($v) => number_format((float) $v, 2))
    @include('admin.finance._range', ['from' => $from, 'to' => $to, 'export' => true])
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Date') }}</th><th>{{ __('Journal') }}</th><th>{{ __('Memo') }}</th><th class="text-end">{{ __('Debit') }}</th><th class="text-end">{{ __('Credit') }}</th><th class="text-end">{{ __('Balance') }}</th></tr></thead><tbody>
        <tr class="table-light"><td colspan="5">{{ __('Brought forward') }}</td><td class="text-end">{{ $m($l['opening']) }}</td></tr>
        @foreach($l['lines'] as $r)<tr><td>{{ \Carbon\Carbon::parse($r->journal_date)->format('d M Y') }}</td><td><a href="{{ route('admin.finance.journals.show', $r->public_id) }}">{{ $r->number }}</a></td><td>{{ $r->line_memo ?: $r->memo }}</td><td class="text-end">{{ bccomp((string) $r->debit, '0', 2) ? $m($r->debit) : '' }}</td><td class="text-end">{{ bccomp((string) $r->credit, '0', 2) ? $m($r->credit) : '' }}</td><td class="text-end">{{ $m($r->balance) }}</td></tr>@endforeach
        <tr class="fw-bold"><td colspan="5">{{ __('Closing balance') }}</td><td class="text-end">{{ $m($l['closing']) }}</td></tr></tbody></table></div></div>
@stop
