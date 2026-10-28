@extends('portal.layout')
@section('title', __('New password'))
@section('content')
<h1>{{ __('Choose a new password') }}</h1>
<form method="POST" action="{{ route('portal.reset.complete') }}" class="card">@csrf<input type="hidden" name="challenge" value="{{ $challenge }}">
<label for="code">{{ __('Code') }}</label><input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus>
<label for="password">{{ __('New password') }}</label><input id="password" name="password" type="password" autocomplete="new-password" required>
<label for="pc">{{ __('Type the password again') }}</label><input id="pc" name="password_confirmation" type="password" autocomplete="new-password" required>
<p><button>{{ __('Change password') }}</button></p></form>
@endsection
