@extends('layouts.adminlte')

@section('page_title', __('Billing against the books'))

@section('page_content')
    @php($m = fn ($v) => number_format((float) $v, 2))
    @include('admin.finance._range', ['from' => $from, 'to' => $to])
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th></th><th class="text-end">{{ __('Billing says') }}</th><th class="text-end">{{ __('The books say') }}</th><th class="text-end">{{ __('Difference') }}</th></tr></thead><tbody>
        @foreach($r['rows'] as $row)<tr><td>{{ __($row['label']) }}</td><td class="text-end">{{ $m($row['billing']) }}</td><td class="text-end">{{ $m($row['ledger']) }}</td><td class="text-end {{ bccomp($row['difference'], '0', 2) !== 0 ? 'text-danger fw-bold' : '' }}">{{ $m($row['difference']) }}</td></tr>@endforeach</tbody></table></div></div>
    <div class="card mt-3"><div class="card-body small">
        <div>{{ __('Postings that failed') }}: <strong>{{ $r['unposted']['failed'] }}</strong> @if($r['unposted']['failed'])<a href="{{ route('admin.finance.postings') }}">{{ __('See them') }}</a>@endif</div>
        <div>{{ __('Invoices not in the books') }}: <strong>{{ $r['unposted']['invoices_not_posted'] }}</strong></div>
        <div>{{ __('Approved adjustments not in the books') }}: <strong>{{ $r['unposted']['adjustments_not_posted'] }}</strong></div>
        <div class="mt-2 fw-bold {{ $r['in_balance'] ? 'text-success' : 'text-danger' }}">{{ $r['in_balance'] ? __('In balance.') : __('Not in balance.') }}</div>
    </div></div>
@stop
