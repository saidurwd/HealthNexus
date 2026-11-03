@extends('portal.layout')
@section('title', __('Payment'))
@section('content')
<h1>{{ __('Complete your payment') }}</h1>
<div class="card"><p>{{ __('Bill') }} <strong>{{ $invoice->invoice_number }}</strong> · {{ __('Amount') }} <strong>{{ number_format((float) $p->amount, 2) }} {{ $p->currency }}</strong> · {{ strtoupper($p->method) }}</p>
<p class="muted">{{ __('You will finish the payment on the payment provider\'s page. Nothing is recorded on your bill until the provider confirms it.') }}</p>
<p><a class="btn" href="{{ $p->redirect_url }}" rel="noopener">{{ __('Go to the payment page') }}</a> <a class="btn alt" href="{{ route('portal.pay.return', $p->public_id) }}">{{ __('I have paid — check now') }}</a></p></div>
@endsection
