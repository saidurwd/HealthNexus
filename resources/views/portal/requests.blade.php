@extends('portal.layout')
@section('title', __('Requests'))
@section('content')
<h1>{{ __('Ask the hospital for something') }}</h1>
<form method="POST" action="{{ route('portal.requests.store') }}" class="card">@csrf
<label for="kind">{{ __('What do you need?') }}</label><select id="kind" name="kind">@foreach($kinds as $k)<option value="{{ $k }}">{{ __(\Illuminate\Support\Str::headline($k)) }}</option>@endforeach</select>
<label for="details">{{ __('Details') }}</label><textarea id="details" name="details" maxlength="2000"></textarea><p><button>{{ __('Send request') }}</button></p></form>
@foreach($rows as $r)<div class="card row"><div><strong>{{ __(\Illuminate\Support\Str::headline($r->kind)) }}</strong><div class="muted">{{ $r->created_at->format('d M') }} · {{ __('due') }} {{ $r->due_on?->format('d M') }}</div>@if($r->resolution)<div>{{ $r->resolution }}</div>@endif</div><span class="pill {{ $r->status === 'done' ? 'good' : '' }}">{{ __(\Illuminate\Support\Str::headline($r->status)) }}</span></div>@endforeach
@endsection
