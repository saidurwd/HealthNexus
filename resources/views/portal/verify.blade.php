@extends('portal.layout')
@section('title', __('Choose a password'))
@section('content')
<h1>{{ __('Enter the code and choose a password') }}</h1>
<form method="POST" action="{{ route('portal.register.complete') }}" class="card">@csrf<input type="hidden" name="challenge" value="{{ $challenge }}">
<label for="code">{{ __('Code') }}</label><input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus>
<label for="password">{{ __('New password') }}</label><input id="password" name="password" type="password" autocomplete="new-password" required><div class="muted">{{ __('At least :n characters with letters and numbers.', ['n' => config('portal.login.password_min_length')]) }}</div>
<label for="pc">{{ __('Type the password again') }}</label><input id="pc" name="password_confirmation" type="password" autocomplete="new-password" required>
<label class="check"><input type="checkbox" name="accept_terms" value="1" required> {{ __('I accept the terms of use and the privacy notice.') }}</label>
<p><button>{{ __('Create my login') }}</button></p></form>
@endsection
