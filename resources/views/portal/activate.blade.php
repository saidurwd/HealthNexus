@extends('portal.layout')
@section('title', __('Invitation'))
@section('content')
<h1>{{ __('I have an invitation code') }}</h1>
<form method="POST" action="{{ route('portal.activate.claim') }}" class="card">@csrf
<label for="code">{{ __('Invitation code') }}</label><input id="code" name="code" required autofocus value="{{ old('code') }}" autocomplete="off">
<label for="dob">{{ __('Date of birth') }}</label><input id="dob" name="date_of_birth" type="date" required value="{{ old('date_of_birth') }}">
<label for="password">{{ __('New password') }}</label><input id="password" name="password" type="password" autocomplete="new-password" required>
<label for="pc">{{ __('Type the password again') }}</label><input id="pc" name="password_confirmation" type="password" autocomplete="new-password" required>
<label class="check"><input type="checkbox" name="accept_terms" value="1" required> {{ __('I accept the terms of use and the privacy notice.') }}</label>
<p><button>{{ __('Create my login') }}</button></p></form>
@endsection
