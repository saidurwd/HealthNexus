@extends('layouts.adminlte')

@section('page_title', __('Chart of accounts'))

@section('page_content')
    @include('admin.finance._flash')
    @if($accounts->isEmpty())<div class="alert alert-info">{{ __('There are no accounts yet.') }} @can('finance.chart.manage')<form method="POST" action="{{ route('admin.finance.accounts.start') }}" class="d-inline">@csrf<button class="btn btn-sm btn-primary">{{ __('Make the starter chart') }}</button></form>@endcan</div>@endif
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Type') }}</th><th>{{ __('Takes postings') }}</th><th>{{ __('Control') }}</th><th>{{ __('Active') }}</th><th></th></tr></thead><tbody>
        @foreach($accounts as $a)
            <tr class="{{ $a->postable ? '' : 'table-light fw-bold' }}"><td><a href="{{ route('admin.finance.ledger', $a->public_id) }}">{{ $a->code }}</a></td>
            <td>@can('finance.chart.manage')<form method="POST" action="{{ route('admin.finance.accounts.update', $a->public_id) }}" class="d-flex gap-1">@csrf<input name="name" value="{{ $a->name }}" class="form-control form-control-sm" aria-label="{{ __('Name') }}"><input type="hidden" name="active" value="0"><div class="form-check mt-1"><input type="checkbox" class="form-check-input" name="active" value="1" @checked($a->active) aria-label="{{ __('Active') }}"></div><button class="btn btn-sm btn-outline-primary">{{ __('Save') }}</button></form>@else{{ $a->name }}@endcan</td>
            <td>{{ __(\Illuminate\Support\Str::headline($a->type)) }}</td><td>{{ $a->postable ? __('Yes') : __('No (heading)') }}</td><td>{{ $a->control ? __(\Illuminate\Support\Str::headline($a->control)) : '' }}</td><td>{{ $a->active ? __('Yes') : __('No') }}</td>
            <td>@can('finance.chart.manage')@unless($a->system)<form method="POST" action="{{ route('admin.finance.accounts.delete', $a->public_id) }}" onsubmit="return confirm('{{ __('Remove this account?') }}')">@csrf<button class="btn btn-sm btn-outline-danger">{{ __('Remove') }}</button></form>@endunless @endcan</td></tr>
        @endforeach</tbody></table></div></div>
    @can('finance.chart.manage')<form method="POST" action="{{ route('admin.finance.accounts.store') }}" class="card mt-3"><div class="card-header"><h3 class="card-title">{{ __('Add an account') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-2"><label class="form-label" for="code">{{ __('Code') }}</label><input id="code" name="code" class="form-control" required value="{{ old('code') }}"></div>
        <div class="col-md-4"><label class="form-label" for="name">{{ __('Name') }}</label><input id="name" name="name" class="form-control" required value="{{ old('name') }}"></div>
        <div class="col-md-2"><label class="form-label" for="type">{{ __('Type') }}</label><select id="type" name="type" class="form-select">@foreach($types as $t)<option value="{{ $t }}">{{ __(\Illuminate\Support\Str::headline($t)) }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label" for="parent_id">{{ __('Under') }}</label><select id="parent_id" name="parent_id" class="form-select"><option value="">—</option>@foreach($accounts->where('postable', false) as $h)<option value="{{ $h->id }}">{{ $h->code }} {{ $h->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="control">{{ __('Control account for') }}</label><select id="control" name="control" class="form-select"><option value="">—</option>@foreach($controls as $c)<option value="{{ $c }}">{{ __(\Illuminate\Support\Str::headline($c)) }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label d-block">{{ __('Takes postings') }}</label><input type="hidden" name="postable" value="0"><div class="form-check"><input type="checkbox" class="form-check-input" name="postable" value="1" id="postable" checked><label class="form-check-label" for="postable">{{ __('Yes (untick for a heading)') }}</label></div></div>
        <div class="col-md-6"><label class="form-label" for="note">{{ __('Note') }}</label><input id="note" name="note" class="form-control"></div>
    </div><div class="card-footer"><button class="btn btn-primary">{{ __('Add') }}</button></div></form>@endcan
@stop
