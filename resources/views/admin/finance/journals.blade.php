@extends('layouts.adminlte')

@section('page_title', __('Journals'))

@section('page_content')
    @include('admin.finance._flash')
    @php($m = fn ($v) => number_format((float) $v, 2))
    <form method="GET" class="card card-body mb-3"><div class="row g-2 align-items-end">
        <div class="col-auto"><label class="form-label" for="from">{{ __('From') }}</label><input type="date" id="from" name="from" value="{{ $from }}" class="form-control"></div>
        <div class="col-auto"><label class="form-label" for="to">{{ __('To') }}</label><input type="date" id="to" name="to" value="{{ $to }}" class="form-control"></div>
        <div class="col-auto"><label class="form-label" for="status">{{ __('Status') }}</label><select id="status" name="status" class="form-select"><option value="">{{ __('Any') }}</option>@foreach(['draft', 'pending', 'posted'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ __(\Illuminate\Support\Str::headline($s)) }}</option>@endforeach</select></div>
        <div class="col-auto"><label class="form-label" for="kind">{{ __('Kind') }}</label><select id="kind" name="kind" class="form-select"><option value="">{{ __('Any') }}</option>@foreach(['manual', 'auto', 'reversal', 'depreciation', 'closing', 'opening'] as $s)<option value="{{ $s }}" @selected(request('kind') === $s)>{{ __(\Illuminate\Support\Str::headline($s)) }}</option>@endforeach</select></div>
        <div class="col-auto"><label class="form-label" for="q">{{ __('Search') }}</label><input id="q" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ __('Number, memo or reference') }}"></div>
        <div class="col-auto"><button class="btn btn-primary">{{ __('Show') }}</button> @can('finance.journal.create')<a class="btn btn-outline-primary" href="{{ route('admin.finance.journals.create') }}">{{ __('New journal') }}</a>@endcan</div></div></form>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Number') }}</th><th>{{ __('Date') }}</th><th>{{ __('Memo') }}</th><th>{{ __('Kind') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Amount') }}</th></tr></thead><tbody>
        @forelse($rows as $j)<tr><td><a href="{{ route('admin.finance.journals.show', $j->public_id) }}">{{ $j->number }}</a></td><td>{{ $j->journal_date->format('d M Y') }}</td><td>{{ $j->memo }}</td><td>{{ __(\Illuminate\Support\Str::headline($j->kind)) }}</td><td><x-ui.status-badge :status="$j->status" /></td><td class="text-end">{{ $m($j->total_debit) }}</td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('No journals in this range.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
