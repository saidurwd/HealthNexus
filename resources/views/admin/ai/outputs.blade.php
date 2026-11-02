@extends('layouts.adminlte')

@section('page_title', __('AI drafts'))

@section('page_content')
    @include('admin.ai._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <div class="d-flex flex-wrap gap-2 mb-3">@foreach(['draft' => 'Waiting', 'accepted' => 'Accepted', 'rejected' => 'Rejected', 'expired' => 'Expired', 'all' => 'All'] as $k => $l)<a class="btn btn-sm {{ $status === $k ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.ai.outputs', ['status' => $k, 'show' => $all ? 'all' : 'mine']) }}">{{ __($l) }}</a>@endforeach
        @if($canAll)<a class="btn btn-sm btn-outline-secondary ms-auto" href="{{ route('admin.ai.outputs', ['status' => $status, 'show' => $all ? 'mine' : 'all']) }}">{{ $all ? __('Only mine') : __('Everyone\'s') }}</a>@endif</div>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('When') }}</th><th>{{ __('What') }}</th><th>{{ __('Function') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
        @forelse($outputs as $o)<tr><td>{{ $o->created_at->setTimezone('Asia/Dhaka')->format('d M H:i') }}</td><td><a href="{{ route('admin.ai.outputs.show', $o->public_id) }}">{{ $o->title }}</a></td><td>{{ $H($o->use_case_code) }}</td><td><x-ui.status-badge :status="$o->status" />@if($o->kind === 'none') <span class="badge text-bg-light border">{{ __('no supported answer') }}</span>@endif</td></tr>
        @empty<tr><td colspan="4" class="text-muted">{{ __('Nothing here.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $outputs->links() }}</div></div>
@stop
