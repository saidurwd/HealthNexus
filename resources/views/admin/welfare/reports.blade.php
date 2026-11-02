@extends('layouts.adminlte')

@section('page_title', __('Welfare reports'))

@section('page_content')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <p class="small text-muted">{{ __('Totals only: no patient name, income or circumstance is shown here.') }}</p>
    <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Received and given, by month') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Month') }}</th><th class="text-end">{{ __('Received') }}</th><th class="text-end">{{ __('Given') }}</th></tr></thead><tbody>
        @foreach($months as $m)<tr><td>{{ $m }}</td><td class="text-end">{{ number_format((float) ($in[$m] ?? 0), 2) }}</td><td class="text-end">{{ number_format(-1 * (float) ($out[$m] ?? 0), 2) }}</td></tr>@endforeach</tbody></table></div></div>
    <div class="row g-3"><div class="col-md-7"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('By fund') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Fund') }}</th><th>{{ __('Kind') }}</th><th class="text-end">{{ __('Received') }}</th><th class="text-end">{{ __('Given') }}</th></tr></thead><tbody>@forelse($byFund as $r)<tr><td>{{ $r->name }}</td><td>{{ $H($r->kind) }}</td><td class="text-end">{{ number_format((float) $r->received, 2) }}</td><td class="text-end">{{ number_format((float) $r->given, 2) }}</td></tr>@empty<tr><td colspan="4" class="text-muted">{{ __('Nothing yet.') }}</td></tr>@endforelse</tbody></table></div></div></div>
        <div class="col-md-5"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('By category of need') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Category') }}</th><th class="text-end">{{ __('Patients') }}</th><th class="text-end">{{ __('Given') }}</th></tr></thead><tbody>@forelse($byCategory as $r)<tr><td>{{ $H($r->category) }}</td><td class="text-end">{{ $r->patients }}</td><td class="text-end">{{ number_format((float) $r->total, 2) }}</td></tr>@empty<tr><td colspan="3" class="text-muted">{{ __('Nothing yet.') }}</td></tr>@endforelse</tbody></table></div></div></div></div>
@stop
