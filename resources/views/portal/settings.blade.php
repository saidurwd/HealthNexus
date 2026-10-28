@extends('portal.layout')
@section('title', __('Settings'))
@section('content')
<h1>{{ __('Settings') }}</h1>
<form method="POST" action="{{ route('portal.settings.preferences') }}" class="card">@csrf<h2 style="margin-top:0">{{ __('How may we contact you?') }}</h2>
<p class="muted">{{ __('Messages about your own care and your security (codes, appointments, results ready, receipts) always reach you. You choose the rest. Marketing messages are off until you switch them on.') }}</p>
<table><thead><tr><th></th>@foreach(['service' => 'Reminders and follow-up', 'marketing' => 'Health camps and offers'] as $p => $t)<th>{{ __($t) }}</th>@endforeach</tr></thead><tbody>
@foreach($channels as $c)<tr><td>{{ ['sms' => 'SMS', 'email' => __('Email'), 'push' => __('App notification'), 'inapp' => __('Portal notice')][$c] ?? $c }}</td>@foreach(['service', 'marketing'] as $p)<td><input type="checkbox" name="pref[{{ $c }}][{{ $p }}]" value="1" @checked($matrix[$c][$p] ?? false) aria-label="{{ $c }} {{ $p }}"></td>@endforeach</tr>@endforeach</tbody></table>
<p class="muted">{{ __('Messages are held back between :a and :b.', ['a' => config('portal.communication.quiet_hours.start'), 'b' => config('portal.communication.quiet_hours.end')]) }}</p>
<h2>{{ __('Signing in') }}</h2><label class="check"><input type="checkbox" name="mfa" value="1" @checked($a->mfa_required)> {{ __('Always ask for a code, even on a phone I have used before') }}</label>
<h2>{{ __('Your permissions') }}</h2>@foreach($consents as $c)<label class="check"><input type="checkbox" name="consent[{{ $c['type'] }}]" value="1" @checked($c['active'])> {{ __(\Illuminate\Support\Str::headline($c['type'])) }} @if($c['since'])<span class="muted">&nbsp;{{ __('since') }} {{ $c['since']->format('d M Y') }}</span>@endif</label>@endforeach
<p><button>{{ __('Save') }}</button></p></form>
<form method="POST" action="{{ route('portal.settings.password') }}" class="card">@csrf<h2 style="margin-top:0">{{ __('Change password') }}</h2>
<label for="c">{{ __('Current password') }}</label><input id="c" name="current" type="password" autocomplete="current-password" required>
<label for="p">{{ __('New password') }}</label><input id="p" name="password" type="password" autocomplete="new-password" required>
<label for="pc">{{ __('Type it again') }}</label><input id="pc" name="password_confirmation" type="password" autocomplete="new-password" required><p><button>{{ __('Change password') }}</button></p></form>
<div class="card"><h2 style="margin-top:0">{{ __('Where you are signed in') }}</h2>@forelse($devices as $d)<div class="row" style="margin-bottom:.4rem"><span>{{ $d->name }} <span class="muted">{{ $d->platform }} · {{ $d->last_seen_at?->diffForHumans() }}</span></span><form method="POST" action="{{ route('portal.settings.devices.out', $d->id) }}">@csrf<button class="alt">{{ __('Sign out') }}</button></form></div>@empty<p class="muted">{{ __('None.') }}</p>@endforelse</div>
<div class="card"><h2 style="margin-top:0">{{ __('Your data') }}</h2><p class="muted">{{ __('Download a copy of what this portal holds about you, or close your login. Closing the login does not change your hospital record.') }}</p><a class="btn alt" href="{{ route('portal.settings.export') }}">{{ __('Download my data') }}</a>
<form method="POST" action="{{ route('portal.settings.close') }}" onsubmit="return confirm('{{ __('Close your login? You can ask the hospital for a new invitation later.') }}')" style="margin-top:1rem">@csrf<label for="cp">{{ __('Your password') }}</label><input id="cp" name="password" type="password" required autocomplete="current-password"><p><button class="bad">{{ __('Close my login') }}</button></p></form></div>
<p class="muted"><a href="{{ route('portal.complaints') }}">{{ __('Make a complaint') }}</a> · <a href="{{ route('portal.requests') }}">{{ __('Ask the hospital for something') }}</a> · <a href="{{ route('portal.forms') }}">{{ __('Forms') }}</a> · <a href="{{ route('portal.help') }}">{{ __('Help') }}</a></p>
@endsection
