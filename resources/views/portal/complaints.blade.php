@extends('portal.layout')
@section('title', __('Complaints'))
@section('content')
<h1>{{ __('Tell us what went wrong') }}</h1>
<form method="POST" action="{{ route('portal.complaints.store') }}" class="card">@csrf
<label for="cat">{{ __('About') }}</label><select id="cat" name="category">@foreach($categories as $c)<option value="{{ $c }}">{{ __(\Illuminate\Support\Str::headline($c)) }}</option>@endforeach</select>
<label for="desc">{{ __('What happened?') }}</label><textarea id="desc" name="description" required maxlength="4000"></textarea>
<p class="muted">{{ __('A person reads every complaint and answers within a few days. For urgent medical problems call :n.', ['n' => config('portal.messaging.emergency_number')]) }}</p><p><button>{{ __('Send complaint') }}</button></p></form>
@foreach($rows as $c)<div class="card"><div class="row"><div><strong>{{ $c->number }}</strong> <span class="muted">{{ __(\Illuminate\Support\Str::headline($c->category)) }} · {{ $c->created_at->format('d M') }}</span></div><span class="pill {{ in_array($c->status, ['resolved', 'closed']) ? 'good' : 'wait' }}">{{ __(\Illuminate\Support\Str::headline($c->status)) }}</span></div>
@if(in_array($c->status, ['resolved', 'closed']) && $c->resolution)<p><strong>{{ __('Our answer') }}:</strong> {{ $c->resolution }}</p>@if(! $c->satisfaction)<form method="POST" action="{{ route('portal.complaints.rate', $c->number) }}" class="row" style="justify-content:flex-start">@csrf<label style="margin:0">{{ __('How satisfied are you with the answer?') }}</label><select name="score" style="width:auto">@foreach([5, 4, 3, 2, 1] as $n)<option value="{{ $n }}">{{ $n }}</option>@endforeach</select><button class="alt">{{ __('Send') }}</button></form>@endif @endif</div>@endforeach
@endsection
