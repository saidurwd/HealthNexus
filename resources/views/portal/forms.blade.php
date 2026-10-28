@extends('portal.layout')
@section('title', __('Forms'))
@section('content')
<h1>{{ __('Forms') }}</h1>
@forelse($rows as $f)<a href="{{ route('portal.forms.show', $f->code) }}" class="card row" style="text-decoration:none;color:inherit"><strong>{{ $f->title }}</strong><span class="muted">{{ count($f->fields) }} {{ __('questions') }}</span></a>@empty<p class="muted">{{ __('No forms to fill in.') }}</p>@endforelse
@endsection
