@extends('layouts.adminlte')

@section('page_title', __('Tax register'))

@section('page_content')
    @php($m = fn ($v) => number_format((float) $v, 2))
    @include('admin.finance._range', ['from' => $from, 'to' => $to, 'export' => true, 'extra' => '<div class="col-auto"><label class="form-label" for="kind">'.e(__('Kind')).'</label><select id="kind" name="kind" class="form-select">'.collect($kinds)->map(fn ($k) => '<option value="'.$k.'"'.($k === $kind ? ' selected' : '').'>'.e(__(\Illuminate\Support\Str::headline($k))).'</option>')->implode('').'</select></div>'])
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Date') }}</th><th>{{ __('Document') }}</th><th>{{ __('Name') }}</th><th>{{ __('BIN / TIN') }}</th><th class="text-end">{{ __('Base') }}</th><th class="text-end">{{ __('Tax') }}</th></tr></thead><tbody>
        @forelse($rows as $r)<tr><td>{{ $r->entry_date->format('d M Y') }}</td><td>{{ $r->document_number }}</td><td>{{ $r->party_name }}</td><td>{{ $r->party_tax_id }}</td><td class="text-end">{{ $m($r->base_amount) }}</td><td class="text-end">{{ $m($r->tax_amount) }}</td></tr>@empty<tr><td colspan="6" class="text-muted">{{ __('Nothing in this range.') }}</td></tr>@endforelse</tbody>
        <tfoot><tr class="fw-bold"><td colspan="4">{{ __('Total') }}</td><td class="text-end">{{ $m($rows->sum('base_amount')) }}</td><td class="text-end">{{ $m($rows->sum('tax_amount')) }}</td></tr></tfoot></table></div></div>
@stop
