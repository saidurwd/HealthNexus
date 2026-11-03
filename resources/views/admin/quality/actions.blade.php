@extends('layouts.adminlte')

@section('page_title', __('Actions'))

@section('page_content')
    @include('admin.quality._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <div class="d-flex flex-wrap gap-2 mb-3">@foreach(array_filter(['mine' => 'Mine', 'all' => auth()->user()->can('quality.action.view') ? 'All' : null, 'open' => auth()->user()->can('quality.action.view') ? 'Open' : null, 'overdue' => auth()->user()->can('quality.action.view') ? 'Overdue' : null, 'verify' => auth()->user()->can('quality.action.verify') ? 'To check' : null]) as $k => $l)<a class="btn btn-sm {{ $view === $k ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.quality.actions', ['show' => $k]) }}">{{ __($l) }}</a>@endforeach</div>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Number') }}</th><th>{{ __('What is to be done') }}</th><th>{{ __('Who') }}</th><th>{{ __('Due') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead><tbody>
        @forelse($actions as $a)<tr><td>{{ $a->number }}<div class="small text-muted">{{ $H($a->action_type) }}</div></td><td>{{ $a->description }} @if($a->done_note)<div class="small text-muted">{{ __('Done') }}: {{ $a->done_note }}</div>@endif @if($a->verify_note)<div class="small text-muted">{{ __('Check') }}: {{ $a->verify_note }}</div>@endif</td><td>{{ $a->owner?->name }}</td>
            <td>{{ $a->due_on->format('d M Y') }} @if(in_array($a->status, ['open', 'in_progress']) && $a->due_on->lt(today()))<x-ui.status-badge status="overdue" />@endif</td><td><x-ui.status-badge :status="$a->status" /></td>
            <td style="min-width: 260px">
                @if(in_array($a->status, ['open', 'in_progress']) && ($a->owner_id === auth()->id() || auth()->user()->can('quality.action.manage')))
                    <form method="POST" action="{{ route('admin.quality.actions.complete', $a->public_id) }}" class="d-flex gap-1">@csrf <input name="note" class="form-control form-control-sm" placeholder="{{ __('What was done') }}" required><button class="btn btn-sm btn-success">{{ __('Done') }}</button></form>
                    @if($a->status === 'open')<form method="POST" action="{{ route('admin.quality.actions.start', $a->public_id) }}" class="mt-1">@csrf<button class="btn btn-sm btn-link p-0">{{ __('Start') }}</button></form>@endif
                @elseif($a->status === 'done')
                    @can('quality.action.verify')@if($a->owner_id !== auth()->id())<form method="POST" action="{{ route('admin.quality.actions.verify', $a->public_id) }}" class="d-flex gap-1">@csrf <select name="effective" class="form-select form-select-sm"><option value="yes">{{ __('It worked') }}</option><option value="no">{{ __('It did not work') }}</option></select><input name="note" class="form-control form-control-sm" placeholder="{{ __('Note') }}"><button class="btn btn-sm btn-primary">{{ __('Save') }}</button></form>@else<span class="small text-muted">{{ __('A second person checks it.') }}</span>@endif @endcan
                @endif
            </td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('No actions.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $actions->links() }}</div></div>
    @can('quality.action.manage')
    <form method="POST" action="{{ route('admin.quality.actions.store') }}" class="card mt-3"><div class="card-header"><h3 class="card-title">{{ __('Add an improvement action') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-5"><input name="description" class="form-control form-control-sm" placeholder="{{ __('What is to be done') }}" required></div>
        <div class="col-md-2"><select name="action_type" class="form-select form-select-sm">@foreach(['improvement', 'corrective', 'preventive'] as $t)<option value="{{ $t }}">{{ $H($t) }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="owner_id" class="form-select form-select-sm">@foreach($staff as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><input type="date" name="due_on" class="form-control form-control-sm" min="{{ today()->toDateString() }}"></div><div class="col-md-1"><button class="btn btn-sm btn-primary w-100">{{ __('Add') }}</button></div></div></form>
    @endcan
@stop
