@extends('layouts.adminlte')

@section('page_title', __('My reports'))

@section('page_content')
    @include('admin.quality._flash')
    <p class="text-muted small">{{ __('Reports you sent with your name. A report sent without your name cannot be listed here, because nobody — including this system — knows it was yours.') }}</p>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Number') }}</th><th>{{ __('When') }}</th><th>{{ __('Kind of event') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead><tbody>
        @forelse($incidents as $i)<tr><td>{{ $i->number }}</td><td>{{ $i->occurred_at->setTimezone('Asia/Dhaka')->format('d M Y H:i') }}</td><td>{{ __(\Illuminate\Support\Str::headline($i->effective('category'))) }}</td><td><x-ui.status-badge :status="$i->status" /></td><td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.quality.incidents.show', $i->public_id) }}">{{ __('Open') }}</a></td></tr>
        @empty<tr><td colspan="5" class="text-muted">{{ __('You have not sent any reports with your name.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $incidents->links() }}</div></div>
@stop
