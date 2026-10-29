@extends('layouts.adminlte')

@section('page_title', __('Who was shown or sent what'))

@section('page_content')
    @include('admin.interop._flash')
    <form method="GET" class="d-flex gap-2 mb-3"><select name="outcome" class="form-select w-auto" aria-label="{{ __('Outcome') }}" onchange="this.form.submit()"><option value="">{{ __('Any outcome') }}</option>@foreach(['allowed', 'denied_consent', 'denied_scope', 'override'] as $o)<option value="{{ $o }}" @selected(request('outcome') === $o)>{{ __(\Illuminate\Support\Str::headline($o)) }}</option>@endforeach</select>
        <select name="partner" class="form-select w-auto" aria-label="{{ __('Partner') }}" onchange="this.form.submit()"><option value="">{{ __('Any partner') }}</option>@foreach($partners as $p)<option value="{{ $p->code }}" @selected(request('partner') === $p->code)>{{ $p->name }}</option>@endforeach</select></form>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('When') }}</th><th>{{ __('Partner') }}</th><th>{{ __('Patient') }}</th><th>{{ __('What') }}</th><th>{{ __('Action') }}</th><th>{{ __('Outcome') }}</th><th>{{ __('Reason') }}</th></tr></thead>
        <tbody>@forelse($rows as $r)<tr class="{{ $r->outcome === 'override' ? 'table-warning' : '' }}"><td>{{ $r->created_at?->format('d M H:i') }}</td><td>{{ $r->partner?->name ?? __('Our own staff') }}</td><td>{{ $r->patient?->first_name }} {{ $r->patient?->last_name }} <span class="text-muted">{{ $r->patient?->enterprise_patient_no }}</span></td><td>{{ $r->resource_type }}</td><td>{{ __(\Illuminate\Support\Str::headline($r->action)) }}</td><td><x-ui.status-badge :status="$r->outcome === 'allowed' ? 'succeeded' : ($r->outcome === 'override' ? 'warning' : 'failed')" /> <span class="small">{{ __(\Illuminate\Support\Str::headline($r->outcome)) }}</span></td><td class="small text-muted">{{ $r->reason }}</td></tr>
        @empty<tr><td colspan="7" class="text-muted">{{ __('Nothing yet.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
