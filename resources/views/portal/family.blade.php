@extends('portal.layout')
@section('title', __('Family'))
@section('content')
<h1>{{ __('Family and access') }}</h1>
<div class="card"><h2 style="margin-top:0">{{ __('People you act for') }}</h2>
@forelse($acting as $a)<div class="row" style="margin-bottom:.5rem"><div><strong>{{ $a->patient->first_name }} {{ $a->patient->last_name }}</strong> ({{ __(\Illuminate\Support\Str::headline($a->relationship)) }}) <span class="pill {{ $a->status === 'active' ? 'good' : 'wait' }}">{{ $a->status === 'active' ? __('Approved') : __('Waiting for the hospital') }}</span><div class="muted">{{ implode(', ', array_map(fn ($s) => __(\Illuminate\Support\Str::headline($s)), (array) $a->scopes)) }}@if($a->valid_to) · {{ __('until') }} {{ $a->valid_to->format('d M Y') }}@endif</div></div>
<form method="POST" action="{{ route('portal.family.end', $a->public_id) }}">@csrf<button class="alt">{{ __('Stop') }}</button></form></div>@empty<p class="muted">{{ __('None.') }}</p>@endforelse</div>
<div class="card"><h2 style="margin-top:0">{{ __('People who can see your record') }}</h2>
@forelse($withAccess as $a)<div class="row" style="margin-bottom:.5rem"><div>{{ __(\Illuminate\Support\Str::headline($a->relationship)) }} · {{ implode(', ', array_map(fn ($s) => __(\Illuminate\Support\Str::headline($s)), (array) $a->scopes)) }}@if($a->valid_to) · {{ __('until') }} {{ $a->valid_to->format('d M Y') }}@endif</div><form method="POST" action="{{ route('portal.family.end', $a->public_id) }}">@csrf<button class="bad">{{ __('Remove their access') }}</button></form></div>@empty<p class="muted">{{ __('Nobody.') }}</p>@endforelse</div>
<form method="POST" action="{{ route('portal.family.request') }}" class="card">@csrf<h2 style="margin-top:0">{{ __('Ask to act for a child or relative') }}</h2>
<p class="muted">{{ __('The hospital checks your request against its records and may ask to see papers. You are told the result.') }}</p>
<label for="identifier">{{ __('Their patient number') }}</label><input id="identifier" name="identifier" required>
<label for="dob">{{ __('Their date of birth') }}</label><input id="dob" name="date_of_birth" type="date" required>
<label for="rel">{{ __('How are you related?') }}</label><select id="rel" name="relationship">@foreach($relationships as $r)<option value="{{ $r }}">{{ __(\Illuminate\Support\Str::headline($r)) }}</option>@endforeach</select>
<label>{{ __('What do you need to do for them?') }}</label>@foreach($scopes as $s)<label class="check"><input type="checkbox" name="scopes[]" value="{{ $s }}" @checked(in_array($s, ['appointments', 'billing']))> {{ __(\Illuminate\Support\Str::headline($s)) }}</label>@endforeach
<label for="ev">{{ __('Anything that helps the hospital check (optional)') }}</label><input id="ev" name="evidence" maxlength="300">
<p><button>{{ __('Send request') }}</button></p></form>
@endsection
