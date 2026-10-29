@extends('layouts.adminlte')

@section('page_title', __('Write-offs'))

@section('page_content')
    @include('admin.finance._flash')
    @php($m = fn ($v) => number_format((float) $v, 2))
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Item') }}</th><th class="text-end">{{ __('Amount') }}</th><th>{{ __('Reason') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead><tbody>
        @forelse($rows as $w)<tr><td>@if($w->receivable)<a href="{{ route('admin.finance.receivables.statement', [$w->receivable->party_type, $w->receivable->party_id]) }}">{{ $w->receivable->document_number ?: '#'.$w->receivable->source_id }}</a>@endif</td><td class="text-end">{{ $m($w->amount) }}</td><td>{{ $w->reason }}</td><td><x-ui.status-badge :status="$w->status" /></td>
            <td>@can('finance.receivable.writeoff_approve')@if($w->status === 'requested')<form method="POST" action="{{ route('admin.finance.writeoffs.decide', $w->public_id) }}" class="d-flex gap-1">@csrf<input name="note" class="form-control form-control-sm" placeholder="{{ __('Note') }}" aria-label="{{ __('Note') }}"><button class="btn btn-sm btn-success" name="decision" value="approve">{{ __('Approve') }}</button><button class="btn btn-sm btn-outline-secondary" name="decision" value="reject">{{ __('Decline') }}</button></form>@endif @endcan</td></tr>
        @empty<tr><td colspan="5" class="text-muted">{{ __('No write-offs.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
