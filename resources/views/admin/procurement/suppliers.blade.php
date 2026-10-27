@extends('layouts.adminlte')

@section('page_title', __('Suppliers'))

@section('page_content')
    @include('admin.procurement._flash')
    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control w-auto" placeholder="{{ __('Code or name') }}">
        <select name="status" class="form-select w-auto"><option value="">{{ __('Any status') }}</option>@foreach(['prospective', 'qualified', 'suspended', 'disqualified'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ __(\Illuminate\Support\Str::headline($s)) }}</option>@endforeach</select>
        <button class="btn btn-outline-secondary">{{ __('Filter') }}</button>
    </form>
    @can('procurement.supplier.manage')
    <form method="POST" action="{{ route('admin.procurement.suppliers.store') }}" class="card mb-3">@csrf
        <div class="card-header"><h3 class="card-title">{{ __('Add a supplier') }}</h3></div>
        <div class="card-body row g-2">
            <div class="col-md-2"><input name="code" class="form-control" placeholder="{{ __('Code') }}" required maxlength="30"></div>
            <div class="col-md-4"><input name="name" class="form-control" placeholder="{{ __('Name') }}" required maxlength="200"></div>
            <div class="col-md-3"><input name="contact_name" class="form-control" placeholder="{{ __('Contact person') }}" maxlength="120"></div>
            <div class="col-md-3"><input name="phone" class="form-control" placeholder="{{ __('Phone') }}" maxlength="40"></div>
            <div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="{{ __('Email') }}"></div>
            <div class="col-md-3"><input name="bin_number" class="form-control" placeholder="{{ __('VAT / BIN number') }}" maxlength="40"></div>
            <div class="col-md-3"><input type="number" name="payment_terms_days" class="form-control" placeholder="{{ __('Days to pay') }}" min="0" max="365"></div>
            <div class="col-md-3"><button class="btn btn-danger">{{ __('Add') }}</button></div>
        </div></form>
    @endcan
    <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>{{ __('Code') }}</th><th>{{ __('Supplier') }}</th><th>{{ __('Phone') }}</th><th class="text-end">{{ __('Score') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>@forelse($rows as $s)<tr><td>{{ $s->code }}</td><td><a href="{{ route('admin.procurement.suppliers.show', $s) }}">{{ $s->name }}</a></td><td>{{ $s->phone }}</td><td class="text-end">{{ $s->rating ?? '—' }}</td><td><x-ui.status-badge :status="$s->status" /></td></tr>
        @empty<tr><td colspan="5" class="text-muted">{{ __('No suppliers.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
