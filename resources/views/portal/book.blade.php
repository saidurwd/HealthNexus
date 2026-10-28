@extends('portal.layout')
@section('title', __('Book an appointment'))
@section('content')
<h1>{{ $reschedule ? __('Choose a new time') : __('Book an appointment') }}</h1>
<form method="GET" class="card">@if($reschedule)<input type="hidden" name="reschedule" value="{{ $reschedule }}">@endif
<label for="doctor">{{ __('Doctor') }}</label><select id="doctor" name="doctor" required><option value="">{{ __('Choose a doctor') }}</option>@foreach($doctors as $d)<option value="{{ $d['id'] }}" @selected($doctor && $doctor['id'] === $d['id'])>{{ $d['name'] }}@if($d['specialty']) — {{ $d['specialty'] }}@endif</option>@endforeach</select>
<label for="date">{{ __('Day') }}</label><input id="date" name="date" type="date" value="{{ $date->toDateString() }}" min="{{ now()->toDateString() }}">
<p><button class="alt">{{ __('Show free times') }}</button></p></form>
@if($doctor)<form method="POST" action="{{ route('portal.appointments.confirm') }}" class="card">@csrf @if($reschedule)<input type="hidden" name="reschedule" value="{{ $reschedule }}">@endif
<h2 style="margin-top:0">{{ $doctor['name'] }} · {{ $date->format('l, d M') }}</h2>
@forelse($slots as $s)<label class="check"><input type="radio" name="slot" value="{{ $s['token'] }}" required> {{ $s['starts_at']->setTimezone('Asia/Dhaka')->format('H:i') }} <span class="muted">&nbsp;({{ $s['minutes'] }} {{ __('min') }})</span></label>@empty<p class="muted">{{ __('No free times on this day. Try another day.') }}</p>@endforelse
@if($slots->isNotEmpty())@unless($reschedule)<label for="reason">{{ __('Reason for the visit (optional)') }}</label><input id="reason" name="reason" maxlength="500">@endunless<p><button>{{ $reschedule ? __('Move my appointment') : __('Book') }}</button></p>@endif</form>@endif
@endsection
