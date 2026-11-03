@extends('portal.layout')
@section('title', $r->title)
@section('content')
<h1>{{ $r->title }}</h1>
@if($v['amended'])<div class="flash warn">{{ __('This report was corrected after it was first issued. This is the latest version.') }}</div>@endif
<div class="card">
@if($v['kind'] === 'lab')<table><thead><tr><th>{{ __('Test') }}</th><th>{{ __('Result') }}</th><th>{{ __('Normal range') }}</th></tr></thead><tbody>@foreach($v['rows'] as $row)<tr><td>{{ $row['test'] }}</td><td><strong>{{ $row['value'] }}</strong> {{ $row['unit'] }} @if($row['flag'])<span class="pill wait">{{ strtoupper($row['flag']) }}</span>@endif</td><td class="muted">{{ $row['range'] }}</td></tr>@endforeach</tbody></table>
@else @if($v['technique'])<h2>{{ __('How it was done') }}</h2><p>{{ $v['technique'] }}</p>@endif @if($v['findings'])<h2>{{ __('What was seen') }}</h2><p style="white-space:pre-wrap">{{ $v['findings'] }}</p>@endif @if($v['impression'])<h2>{{ __('Summary') }}</h2><p style="white-space:pre-wrap">{{ $v['impression'] }}</p>@endif @if($v['recommendation'])<h2>{{ __('Advice') }}</h2><p>{{ $v['recommendation'] }}</p>@endif @if($v['viewer_url'])<p><a class="btn alt" href="{{ $v['viewer_url'] }}" rel="noopener" target="_blank">{{ __('View the images') }}</a></p>@endif @endif
</div>
<p class="muted">{{ __('Results need a doctor to explain them. Do not change your treatment because of a number here.') }}</p>
<form method="POST" action="{{ route('portal.documents.share') }}" class="noprint">@csrf<input type="hidden" name="type" value="{{ $r->source_type }}"><input type="hidden" name="id" value="{{ $r->public_id }}"><button class="alt">{{ __('Open a printable copy') }}</button></form>
<p><a href="{{ route('portal.results') }}">{{ __('Back to results') }}</a></p>
@endsection
