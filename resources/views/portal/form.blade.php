@extends('portal.layout')
@section('title', $f->title)
@section('content')
<h1>{{ $f->title }}</h1>
<form method="POST" action="{{ route('portal.forms.submit', $f->code) }}" class="card">@csrf
@foreach($f->fields as $q)<label for="q_{{ $q['key'] }}">{{ $q['label'] }}@if($q['required'] ?? false) *@endif</label>
@if($q['type'] === 'textarea')<textarea id="q_{{ $q['key'] }}" name="answers[{{ $q['key'] }}]" maxlength="2000" @required($q['required'] ?? false)></textarea>
@elseif($q['type'] === 'yesno')<select id="q_{{ $q['key'] }}" name="answers[{{ $q['key'] }}]" @required($q['required'] ?? false)><option value=""></option><option value="yes">{{ __('Yes') }}</option><option value="no">{{ __('No') }}</option></select>
@elseif($q['type'] === 'select')<select id="q_{{ $q['key'] }}" name="answers[{{ $q['key'] }}]" @required($q['required'] ?? false)><option value=""></option>@foreach($q['options'] ?? [] as $o)<option value="{{ $o }}">{{ $o }}</option>@endforeach</select>
@else<input id="q_{{ $q['key'] }}" name="answers[{{ $q['key'] }}]" type="{{ in_array($q['type'], ['number', 'date']) ? $q['type'] : 'text' }}" @if($q['type'] === 'number') step="any" @endif @required($q['required'] ?? false)>@endif @endforeach
<p><button>{{ __('Send') }}</button></p></form>
@endsection
