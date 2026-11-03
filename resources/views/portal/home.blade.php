@extends('portal.layout')
@section('title', __('Home'))
@section('content')
<h1>{{ __('Hello') }}, {{ $subjects->firstWhere('relation', 'self')['patient']->first_name ?? '' }}</h1>
@if($subjects->count() > 1)<form method="POST" action="{{ route('portal.switch') }}" class="card row">@csrf<label for="for" style="margin:0">{{ __('You are looking at') }}</label>
<select id="for" name="for" onchange="this.form.submit()" style="max-width:20rem">@foreach($subjects as $s)<option value="{{ $s['relation'] === 'self' ? '' : $s['patient']->public_id }}" @selected(($for ?? '') === ($s['relation'] === 'self' ? '' : $s['patient']->public_id))>{{ $s['patient']->first_name }} {{ $s['patient']->last_name }}@if($s['relation'] !== 'self') ({{ __(\Illuminate\Support\Str::headline($s['relation'])) }})@endif</option>@endforeach</select><noscript><button>{{ __('Switch') }}</button></noscript></form>@endif
<div class="emerg">{{ __('In an emergency call :n or go to the nearest emergency department. Do not use messages for urgent problems.', ['n' => config('portal.messaging.emergency_number')]) }}</div>
<div class="tiles"><a class="tile" href="{{ route('portal.results') }}" style="text-decoration:none;color:inherit"><b>{{ $newResults }}</b>{{ __('new results this week') }}</a><a class="tile" href="{{ route('portal.billing') }}" style="text-decoration:none;color:inherit"><b>{{ $billsDue }}</b>{{ __('bills to pay') }}</a><a class="tile" href="{{ route('portal.notifications') }}" style="text-decoration:none;color:inherit"><b>{{ $unread }}</b>{{ __('new notices') }}</a><a class="tile" href="{{ route('portal.prescriptions') }}" style="text-decoration:none;color:inherit"><b>{{ $refills }}</b>{{ __('refill requests waiting') }}</a></div>
<div class="card"><h2 style="margin-top:0">{{ __('Next appointment') }}</h2>
@if($next)<div class="row"><div><strong>{{ $next->appointment_date->format('l, d M Y') }}</strong> {{ $next->appointment_time->format('H:i') }}<div class="muted">{{ $next->provider?->name }} · {{ $next->branch?->name }}</div></div><a class="btn alt" href="{{ route('portal.appointments') }}">{{ __('Manage') }}</a></div>
@if($queue)<p class="flash warn" style="margin:.7rem 0 0">{{ __('Your token is :t.', ['t' => $queue['token']]) }} @if($queue['called']){{ __('You are being called now.') }}@else{{ __(':n people are ahead of you.', ['n' => $queue['ahead']]) }}@endif</p>@endif
@else<p class="muted">{{ __('You have no appointment booked.') }}</p><a class="btn" href="{{ route('portal.appointments.book') }}">{{ __('Book an appointment') }}</a>@endif</div>
@endsection
