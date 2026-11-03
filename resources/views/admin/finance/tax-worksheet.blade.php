@extends('layouts.adminlte')

@section('page_title', __('VAT return worksheet'))

@section('page_content')
    @php($m = fn ($v) => number_format((float) $v, 2))
    <div class="alert alert-warning small">{{ __('A worksheet to help the accountant prepare the return. Check it against the tax authority\'s current rules before filing.') }}</div>
    @include('admin.finance._range', ['from' => $from, 'to' => $to, 'export' => true])
    <div class="card mb-3"><table class="table table-sm mb-0"><thead><tr><th></th><th class="text-end">{{ __('Base') }}</th><th class="text-end">{{ __('Tax') }}</th><th class="text-end">{{ __('Documents') }}</th></tr></thead><tbody>
        <tr><td>{{ __('Output VAT (sales)') }}</td><td class="text-end">{{ $m($w['output']['base']) }}</td><td class="text-end">{{ $m($w['output']['tax']) }}</td><td class="text-end">{{ $w['output']['count'] }}</td></tr>
        <tr><td>{{ __('Input VAT (purchases)') }}</td><td class="text-end">{{ $m($w['input']['base']) }}</td><td class="text-end">{{ $m($w['input']['tax']) }}</td><td class="text-end">{{ $w['input']['count'] }}</td></tr>
        <tr class="fw-bold"><td>{{ __('Net VAT to pay (output − input); a negative figure is carried forward') }}</td><td></td><td class="text-end">{{ $m($w['net_payable']) }}</td><td></td></tr>
        <tr><td>{{ __('AIT/TDS withheld from suppliers') }}</td><td></td><td class="text-end">{{ $m($w['withheld_ait']) }}</td><td></td></tr>
        <tr><td>{{ __('VAT withheld at source') }}</td><td></td><td class="text-end">{{ $m($w['withheld_vat']) }}</td><td></td></tr></tbody></table></div>
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Does the register agree with the books?') }}</h3></div><table class="table table-sm mb-0"><thead><tr><th></th><th class="text-end">{{ __('Register') }}</th><th class="text-end">{{ __('Books') }}</th><th class="text-end">{{ __('Difference') }}</th></tr></thead><tbody>
        @foreach($w['checks'] as $c)<tr><td>{{ __($c['label']) }}</td><td class="text-end">{{ $m($c['register']) }}</td><td class="text-end">{{ $m($c['ledger']) }}</td><td class="text-end {{ bccomp($c['difference'], '0', 2) !== 0 ? 'text-danger fw-bold' : '' }}">{{ $m($c['difference']) }}</td></tr>@endforeach</tbody></table></div>
@stop
