@extends('portal.layout')
@section('title', __('Messages'))
@section('content')
<h1>{{ __('Messages') }}</h1>
<div class="emerg">{{ __('Messages are not for emergencies and are not watched all the time. In an emergency call :n.', ['n' => $n]) }}</div>
@forelse($rows as $t)<a href="{{ route('portal.threads.show', $t->public_id) }}" class="card row" style="text-decoration:none;color:inherit"><div><strong>{{ $t->subject }}</strong><div class="muted">{{ __(\Illuminate\Support\Str::headline($t->category)) }} · {{ $t->last_message_at?->diffForHumans() }}</div></div><span class="pill {{ $t->status === 'answered' ? 'good' : '' }}">{{ __(\Illuminate\Support\Str::headline($t->status)) }}</span></a>@empty<p class="muted">{{ __('No messages yet.') }}</p>@endforelse
{{ $rows->links() }}
<form method="POST" action="{{ route('portal.threads.start') }}" class="card">@csrf<h2 style="margin-top:0">{{ __('New message') }}</h2>
<label for="subject">{{ __('Subject') }}</label><input id="subject" name="subject" required maxlength="160" value="{{ old('subject') }}">
<label for="cat">{{ __('About') }}</label><select id="cat" name="category">@foreach($categories as $c)<option value="{{ $c }}">{{ __(\Illuminate\Support\Str::headline($c)) }}</option>@endforeach</select>
<label for="body">{{ __('Your message') }}</label><textarea id="body" name="body" required maxlength="4000">{{ old('body') }}</textarea>
<p class="muted">{{ __('A message cannot order tests or medicines. To get those, book a visit.') }}</p><p><button>{{ __('Send') }}</button></p></form>
@endsection
