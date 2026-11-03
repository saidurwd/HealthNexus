@extends('layouts.adminlte')

@section('page_title', ($bank?->name).' — '.($s->file_name ?: '#'.$s->id))

@section('page_content')
    @include('admin.finance._flash')
    @php($m = fn ($v) => number_format((float) $v, 2))
    <div class="card mb-3"><div class="card-body row g-2 small">
        <div class="col-md-3"><div class="text-muted">{{ __('Statement closing balance') }}</div>{{ $summary['statement_closing'] !== null ? $m($summary['statement_closing']) : '—' }}</div>
        <div class="col-md-3"><div class="text-muted">{{ __('Books on the last day') }}</div>{{ $m($summary['ledger']) }}</div>
        <div class="col-md-3"><div class="text-muted">{{ __('Difference') }}</div><span class="{{ $summary['difference'] !== null && bccomp($summary['difference'], '0', 2) !== 0 ? 'text-danger' : '' }}">{{ $summary['difference'] !== null ? $m($summary['difference']) : '—' }}</span></div>
        <div class="col-md-3"><div class="text-muted">{{ __('Lines not matched') }}</div>{{ $summary['unmatched'] }} (+{{ $m($summary['unmatched_in']) }} / −{{ $m($summary['unmatched_out']) }})</div></div>
        @can('finance.bank.reconcile')<div class="card-footer d-flex gap-2"><form method="POST" action="{{ route('admin.finance.statements.act', $s->public_id) }}">@csrf<input type="hidden" name="action" value="auto"><button class="btn btn-sm btn-outline-primary">{{ __('Match what can be matched') }}</button></form>
            @if($s->status === 'open')<form method="POST" action="{{ route('admin.finance.statements.act', $s->public_id) }}">@csrf<input type="hidden" name="action" value="done"><button class="btn btn-sm btn-success" @disabled(! $summary['reconciled'])>{{ __('Mark as reconciled') }}</button></form>@endif</div>@endcan</div>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Date') }}</th><th>{{ __('Description') }}</th><th>{{ __('Reference') }}</th><th class="text-end">{{ __('In') }}</th><th class="text-end">{{ __('Out') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead><tbody>
        @foreach($lines as $l)<tr><td>{{ $l->txn_date->format('d M Y') }}</td><td>{{ $l->description }}</td><td>{{ $l->reference }}</td><td class="text-end">{{ bccomp((string) $l->money_in, '0', 2) ? $m($l->money_in) : '' }}</td><td class="text-end">{{ bccomp((string) $l->money_out, '0', 2) ? $m($l->money_out) : '' }}</td><td><x-ui.status-badge :status="$l->status" /> <small class="text-muted">{{ $l->matched_type ? __(\Illuminate\Support\Str::headline($l->matched_type)) : '' }} {{ $l->note }}</small></td>
            <td>@can('finance.bank.reconcile')@if($l->status === 'unmatched')<form method="POST" action="{{ route('admin.finance.statements.act', $s->public_id) }}" class="d-flex gap-1">@csrf<input type="hidden" name="line_id" value="{{ $l->id }}"><select name="account" class="form-select form-select-sm" aria-label="{{ __('Book it to') }}" style="width:11rem">@foreach($accounts as $a)<option value="{{ $a->code }}">{{ $a->code }} {{ $a->name }}</option>@endforeach</select><input name="note" class="form-control form-control-sm" placeholder="{{ __('Note') }}" aria-label="{{ __('Note') }}"><button class="btn btn-sm btn-outline-primary" name="action" value="post">{{ __('Book it') }}</button><button class="btn btn-sm btn-outline-secondary" name="action" value="ignore">{{ __('Set aside') }}</button></form>@endif @endcan</td></tr>@endforeach</tbody></table></div></div>
@stop
