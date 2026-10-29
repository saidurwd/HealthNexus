@extends('layouts.adminlte')

@section('page_title', __('Bank statements'))

@section('page_content')
    @include('admin.finance._flash')
    @php($m = fn ($v) => number_format((float) $v, 2))
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Account') }}</th><th>{{ __('File') }}</th><th>{{ __('From') }}</th><th>{{ __('To') }}</th><th class="text-end">{{ __('Lines') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
        @forelse($rows as $s)<tr><td>{{ $s->bank?->name }}</td><td><a href="{{ route('admin.finance.statements.show', $s->public_id) }}">{{ $s->file_name ?: '#'.$s->id }}</a></td><td>{{ $s->period_from->format('d M Y') }}</td><td>{{ $s->period_to->format('d M Y') }}</td><td class="text-end">{{ $s->line_count }}</td><td><x-ui.status-badge :status="$s->status" /></td></tr>@empty<tr><td colspan="6" class="text-muted">{{ __('No statements yet.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
    @can('finance.bank.reconcile')<form method="POST" action="{{ route('admin.finance.statements.import') }}" enctype="multipart/form-data" class="card mt-3"><div class="card-header"><h3 class="card-title">{{ __('Import a statement') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-3"><label class="form-label" for="bank_account_id">{{ __('Account') }}</label><select id="bank_account_id" name="bank_account_id" class="form-select">@foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="file">{{ __('CSV file: date, description, reference, money in, money out') }}</label><input type="file" id="file" name="file" accept=".csv,.txt" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label" for="opening">{{ __('Opening balance') }}</label><input id="opening" name="opening" class="form-control"></div>
        <div class="col-md-2"><label class="form-label" for="closing">{{ __('Closing balance') }}</label><input id="closing" name="closing" class="form-control"></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary">{{ __('Import') }}</button></div></div></form>@endcan
    @can('finance.bank.manage')@if($settlements->isNotEmpty())<form method="POST" action="{{ route('admin.finance.deposits.settlement') }}" class="card mt-3"><div class="card-header"><h3 class="card-title">{{ __('Online payment settlements not yet in the books') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-6"><label class="form-label" for="settlement_id">{{ __('Settlement file') }}</label><select id="settlement_id" name="settlement_id" class="form-select">@foreach($settlements as $s)<option value="{{ $s->id }}">{{ $s->file_name ?: '#'.$s->id }} — {{ $s->period_from }} → {{ $s->period_to }} — {{ $m($s->total) }} ({{ __('fees') }} {{ $m($s->fees) }})</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label" for="sbank">{{ __('Landed in') }}</label><select id="sbank" name="bank_account_id" class="form-select">@foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary">{{ __('Book it') }}</button></div></div></form>@endif @endcan
@stop
