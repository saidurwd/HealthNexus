@extends('layouts.adminlte')

@section('page_title', __('Research datasets'))

@section('page_content')
    @include('admin.analytics._flash')
    <div class="alert alert-info small">{{ __('A research dataset has no names, phone numbers or ids. Each patient becomes a reference made only for that dataset, dates are reduced to the month, and any group of fewer than :k people is left out. A second person must approve every request.', ['k' => $k]) }}</div>
    <div class="card mb-3"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Dataset') }}</th><th>{{ __('What for') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Rows') }}</th><th class="text-end">{{ __('Left out') }}</th><th></th></tr></thead><tbody>
        @forelse($rows as $d)<tr><td>{{ $d->name }}<div class="small text-muted">{{ $sources[$d->definition['source']]['label'] ?? $d->definition['source'] }}: {{ implode(', ', $d->definition['columns']) }} · {{ $d->definition['from'] }} → {{ $d->definition['to'] }}</div></td><td class="small">{{ $d->purpose }}</td><td><x-ui.status-badge :status="$d->status" /></td><td class="text-end">{{ $d->row_count }}</td><td class="text-end">{{ $d->suppressed_rows }}</td>
            <td>@if($d->status === 'requested')@can('analytics.research.approve')<form method="POST" action="{{ route('admin.analytics.research.decide', $d->public_id) }}" class="d-flex gap-1">@csrf<input name="note" class="form-control form-control-sm" placeholder="{{ __('Note') }}" aria-label="{{ __('Note') }}"><button class="btn btn-sm btn-success" name="decision" value="approve">{{ __('Approve') }}</button><button class="btn btn-sm btn-outline-secondary" name="decision" value="reject">{{ __('Decline') }}</button></form>@endcan
                @elseif($d->status === 'generated')@can('analytics.research.download')<a class="btn btn-sm btn-outline-primary" href="{{ route('admin.analytics.research.download', $d->public_id) }}"><i class="bi bi-download"></i> {{ __('Download') }}</a>@endcan @endif</td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('No requests yet.') }}</td></tr>@endforelse</tbody></table></div></div>
    <form method="POST" action="{{ route('admin.analytics.research.request') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Ask for a dataset') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-4"><label class="form-label" for="name">{{ __('Name') }}</label><input id="name" name="name" class="form-control" required maxlength="150"></div>
        <div class="col-md-8"><label class="form-label" for="purpose">{{ __('What is it for? (the approver reads this)') }}</label><input id="purpose" name="purpose" class="form-control" required maxlength="400"></div>
        <div class="col-md-3"><label class="form-label" for="source">{{ __('About') }}</label><select id="source" name="source" class="form-select">@foreach($sources as $key => $s)<option value="{{ $key }}">{{ __($s['label']) }}</option>@endforeach</select></div>
        <div class="col-md-5"><label class="form-label" for="columns">{{ __('Columns') }}</label><select id="columns" name="columns[]" class="form-select" multiple size="6">@foreach($sources as $key => $s)<optgroup label="{{ __($s['label']) }}">@foreach(array_keys($s['columns']) as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach</optgroup>@endforeach</select><div class="form-text">{{ __('Choose columns that belong to the subject you picked.') }}</div></div>
        <div class="col-md-2"><label class="form-label" for="from">{{ __('From') }}</label><input type="date" id="from" name="from" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label" for="to">{{ __('To') }}</label><input type="date" id="to" name="to" class="form-control" required></div>
    </div><div class="card-footer"><button class="btn btn-primary">{{ __('Ask') }}</button></div></form>
@stop
