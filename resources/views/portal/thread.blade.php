@extends('portal.layout')
@section('title', $t->subject)
@section('content')
<h1>{{ $t->subject }}</h1>
<div class="emerg">{{ __('In an emergency call :n. Do not wait for a reply here.', ['n' => $n]) }}</div>
<div class="card">@foreach($messages as $m)<div style="margin-bottom:.8rem;{{ $m->sender === 'patient' ? 'text-align:right' : '' }}"><div class="bubble {{ $m->sender === 'patient' ? 'me' : '' }}">{{ $m->body }}</div><div class="muted">{{ $m->sender === 'patient' ? __('You') : ($m->sender === 'staff' ? __('Hospital') : __('Notice')) }} · {{ $m->created_at?->format('d M H:i') }}</div></div>@endforeach</div>
@if($t->status !== 'closed')<form method="POST" action="{{ route('portal.threads.reply', $t->public_id) }}" class="card">@csrf<label for="body">{{ __('Reply') }}</label><textarea id="body" name="body" required maxlength="4000"></textarea><p><button>{{ __('Send') }}</button></p></form>@else<p class="muted">{{ __('This conversation is closed. Start a new message if you need more help.') }}</p>@endif
<p><a href="{{ route('portal.threads') }}">{{ __('Back to messages') }}</a></p>
@endsection
