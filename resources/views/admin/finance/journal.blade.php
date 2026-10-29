@extends('layouts.adminlte')

@section('page_title', $j->number)

@section('page_content')
    @include('admin.finance._flash')
    @php($m = fn ($v) => number_format((float) $v, 2))
    <div class="card mb-3"><div class="card-body row g-2 small">
        <div class="col-md-3"><div class="text-muted">{{ __('Date') }}</div>{{ $j->journal_date->format('d M Y') }}</div>
        <div class="col-md-3"><div class="text-muted">{{ __('Status') }}</div><x-ui.status-badge :status="$j->status" /> {{ __(\Illuminate\Support\Str::headline($j->kind)) }}</div>
        <div class="col-md-6"><div class="text-muted">{{ __('Memo') }}</div>{{ $j->memo }}</div>
        @if($j->reference)<div class="col-md-3"><div class="text-muted">{{ __('Reference') }}</div>{{ $j->reference }}</div>@endif
        @if($j->reject_note)<div class="col-12 text-danger">{{ __('Sent back') }}: {{ $j->reject_note }}</div>@endif
        @if($original)<div class="col-12">{{ __('This reverses') }} <a href="{{ route('admin.finance.journals.show', $original->public_id) }}">{{ $original->number }}</a></div>@endif
        @if($reversal)<div class="col-12 text-warning">{{ __('Reversed by') }} <a href="{{ route('admin.finance.journals.show', $reversal->public_id) }}">{{ $reversal->number }}</a></div>@endif
    </div></div>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>#</th><th>{{ __('Account') }}</th><th>{{ __('Cost centre') }}</th><th>{{ __('Whose') }}</th><th class="text-end">{{ __('Debit') }}</th><th class="text-end">{{ __('Credit') }}</th><th>{{ __('Memo') }}</th></tr></thead><tbody>
        @foreach($lines as $l)<tr><td>{{ $l->line_no }}</td><td>{{ $l->account->code }} {{ $l->account->name }}</td><td>{{ $l->cost_center_id ? ($centers[$l->cost_center_id] ?? '') : '' }}</td><td>{{ $l->party_type ? __(\Illuminate\Support\Str::headline($l->party_type)).' #'.$l->party_id : '' }}</td><td class="text-end">{{ bccomp((string) $l->debit, '0', 2) ? $m($l->debit) : '' }}</td><td class="text-end">{{ bccomp((string) $l->credit, '0', 2) ? $m($l->credit) : '' }}</td><td>{{ $l->memo }}</td></tr>@endforeach
        <tr class="fw-bold"><td colspan="4" class="text-end">{{ __('Total') }}</td><td class="text-end">{{ $m($j->total_debit) }}</td><td class="text-end">{{ $m($j->total_credit) }}</td><td></td></tr></tbody></table></div>
        <div class="card-footer d-flex gap-2 flex-wrap">
            @if($j->status === 'draft')@can('finance.journal.create')<form method="POST" action="{{ route('admin.finance.journals.act', $j->public_id) }}">@csrf<input type="hidden" name="action" value="submit"><button class="btn btn-primary">{{ __('Send for approval') }}</button></form>
                <form method="POST" action="{{ route('admin.finance.journals.act', $j->public_id) }}" onsubmit="return confirm('{{ __('Delete this draft?') }}')">@csrf<input type="hidden" name="action" value="discard"><button class="btn btn-outline-danger">{{ __('Delete the draft') }}</button></form>@endcan @endif
            @if($j->status === 'pending')@can('finance.journal.approve')<form method="POST" action="{{ route('admin.finance.journals.act', $j->public_id) }}">@csrf<input type="hidden" name="action" value="approve"><button class="btn btn-success">{{ __('Approve and post') }}</button></form>
                <form method="POST" action="{{ route('admin.finance.journals.act', $j->public_id) }}" class="d-flex gap-1">@csrf<input type="hidden" name="action" value="reject"><input name="note" class="form-control" placeholder="{{ __('What is wrong?') }}" required aria-label="{{ __('Reason') }}"><button class="btn btn-outline-warning">{{ __('Send back') }}</button></form>@endcan @endif
            @if($j->status === 'posted' && ! $reversal && ! $original)@can('finance.journal.reverse')<form method="POST" action="{{ route('admin.finance.journals.act', $j->public_id) }}" class="d-flex gap-1">@csrf<input type="hidden" name="action" value="reverse"><input name="note" class="form-control" placeholder="{{ __('Why is it reversed?') }}" required aria-label="{{ __('Reason') }}"><button class="btn btn-outline-danger">{{ __('Reverse') }}</button></form>@endcan @endif
        </div></div>
@stop
