@extends('layouts.adminlte')

@section('page_title', __('Posting problems'))

@section('page_content')
    @include('admin.finance._flash')
    <div class="d-flex gap-2 mb-3 align-items-center">
        @foreach(['failed' => 'Failed', 'posted' => 'Posted', 'skipped' => 'Skipped', 'all' => 'All'] as $k => $label)<a class="btn btn-sm {{ $status === $k ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.finance.postings', ['status' => $k]) }}">{{ __($label) }}</a>@endforeach
        @can('finance.posting.retry')<form method="POST" action="{{ route('admin.finance.postings.retry') }}" class="ms-auto">@csrf<button class="btn btn-sm btn-warning">{{ __('Look for missed entries and try again') }}</button></form>@endcan
    </div>
    <p class="text-muted small">{{ __('Money events from Billing, insurance and stores are posted to the books as they happen. When one cannot be posted (a locked period, an account that is not set) it waits here with the reason; Billing is never held up.') }}</p>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('What') }}</th><th>{{ __('Event') }}</th><th>{{ __('Status') }}</th><th>{{ __('Reason') }}</th><th>{{ __('Tries') }}</th><th>{{ __('Last try') }}</th></tr></thead><tbody>
        @forelse($rows as $r)<tr><td>{{ __(\Illuminate\Support\Str::headline($r->source_type)) }} #{{ $r->source_id }}</td><td>{{ $r->event_key }}</td><td><x-ui.status-badge :status="$r->status" /></td><td class="small">{{ $r->error }}</td><td>{{ $r->attempts }}</td><td>{{ $r->last_attempt_at?->setTimezone('Asia/Dhaka')->format('d M H:i') }}</td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('Nothing here.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
