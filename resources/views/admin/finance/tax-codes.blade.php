@extends('layouts.adminlte')

@section('page_title', __('Tax codes'))

@section('page_content')
    @include('admin.finance._flash')
    <div class="alert alert-warning small">{{ __('Tax rates and rules must be confirmed with the hospital\'s accountant. This system records the tax shown on each document; it does not decide a rate.') }}</div>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Kind') }}</th><th class="text-end">{{ __('Rate %') }}</th><th>{{ __('Account') }}</th><th>{{ __('Active') }}</th></tr></thead><tbody>
        @forelse($rows as $c)<tr><td>{{ $c->code }}</td><td>{{ $c->name }}</td><td>{{ __(\Illuminate\Support\Str::headline($c->kind)) }}</td><td class="text-end">{{ rtrim(rtrim((string) $c->rate, '0'), '.') }}</td><td>{{ $c->account?->code }} {{ $c->account?->name }}</td><td>{{ $c->active ? __('Yes') : __('No') }}</td></tr>@empty<tr><td colspan="6" class="text-muted">{{ __('No tax codes yet.') }}</td></tr>@endforelse</tbody></table></div></div>
    @can('finance.tax.manage')<form method="POST" action="{{ route('admin.finance.tax-codes.save') }}" class="card mt-3"><div class="card-header"><h3 class="card-title">{{ __('Add a tax code') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-2"><label class="form-label" for="code">{{ __('Code') }}</label><input id="code" name="code" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label" for="name">{{ __('Name') }}</label><input id="name" name="name" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label" for="kind">{{ __('Kind') }}</label><select id="kind" name="kind" class="form-select">@foreach($kinds as $k)<option value="{{ $k }}">{{ __(\Illuminate\Support\Str::headline($k)) }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label" for="rate">{{ __('Rate %') }}</label><input id="rate" name="rate" class="form-control" required value="0"></div>
        <div class="col-md-4"><label class="form-label" for="account_id">{{ __('Account') }}</label><select id="account_id" name="account_id" class="form-select">@foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->code }} {{ $a->name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label" for="note">{{ __('Note') }}</label><input id="note" name="note" class="form-control"></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary">{{ __('Add') }}</button></div></div></form>@endcan
@stop
