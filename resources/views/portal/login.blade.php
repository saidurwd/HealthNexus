@extends('portal.layout')
@section('title', __('Sign in'))
@section('content')
<h1>{{ __('Sign in to your health portal') }}</h1>
<form method="POST" action="{{ route('portal.login.attempt') }}" class="card">@csrf
<label for="phone">{{ __('Mobile number') }}</label><input id="phone" name="phone" inputmode="tel" autocomplete="username" value="{{ old('phone') }}" required autofocus>
<label for="password">{{ __('Password') }}</label><input id="password" name="password" type="password" autocomplete="current-password" required>
<p><button>{{ __('Sign in') }}</button></p>
<p class="muted"><a href="{{ route('portal.forgot') }}">{{ __('Forgot your password?') }}</a></p></form>
<div class="card"><strong>{{ __('New here?') }}</strong><p class="muted">{{ __('You can join with your patient number, date of birth and the mobile number the hospital has for you.') }}</p><a class="btn alt" href="{{ route('portal.register') }}">{{ __('Create my login') }}</a> <a class="btn alt" href="{{ route('portal.activate') }}">{{ __('I have an invitation code') }}</a></div>
@endsection
