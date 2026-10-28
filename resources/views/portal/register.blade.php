@extends('portal.layout')
@section('title', __('Create my login'))
@section('content')
<h1>{{ __('Create my login') }}</h1>
<form method="POST" action="{{ route('portal.register.start') }}" class="card">@csrf
<p class="muted">{{ __('We check these against the hospital record. We never tell you whether a record exists. If they match, a code goes to the mobile number the hospital has for you.') }}</p>
<label for="identifier">{{ __('Patient number or national ID') }}</label><input id="identifier" name="identifier" value="{{ old('identifier') }}" required>
<label for="dob">{{ __('Date of birth') }}</label><input id="dob" name="date_of_birth" type="date" value="{{ old('date_of_birth') }}" required>
<label for="phone">{{ __('Mobile number') }}</label><input id="phone" name="phone" inputmode="tel" value="{{ old('phone') }}" required>
<p><button>{{ __('Send me a code') }}</button> <a href="{{ route('portal.login') }}">{{ __('Back') }}</a></p></form>
<p class="muted">{{ __('If the mobile number on your record is old, ask at the front desk for an invitation code.') }}</p>
@endsection
