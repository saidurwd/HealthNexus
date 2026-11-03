@extends('portal.layout')
@section('title', __('Forgot password'))
@section('content')
<h1>{{ __('Forgot your password?') }}</h1>
<form method="POST" action="{{ route('portal.forgot.start') }}" class="card">@csrf
<label for="phone">{{ __('Mobile number') }}</label><input id="phone" name="phone" inputmode="tel" required autofocus>
<p><button>{{ __('Send me a code') }}</button> <a href="{{ route('portal.login') }}">{{ __('Back') }}</a></p></form>
@endsection
