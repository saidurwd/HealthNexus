@extends('portal.layout')
@section('title', $title)
@section('content')
<h1>{{ $title }}</h1>
<form method="POST" action="{{ $action }}" class="card">@csrf<input type="hidden" name="challenge" value="{{ $challenge }}">
<p class="muted">{{ __('We sent a code to your phone. It works for a few minutes.') }}</p>
<label for="code">{{ __('Code') }}</label><input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus>
<p><button>{{ __('Continue') }}</button></p></form>
@endsection
