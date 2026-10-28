@extends('portal.layout')
@section('title', __('Results'))
@section('content')
<h1>{{ __('My results') }}</h1>
@if($waiting->isNotEmpty())<div class="flash warn">{{ __(':n report(s) are not shown here yet. Your doctor will go through them with you, or release them when they are ready.', ['n' => $waiting->count()]) }}</div>@endif
@forelse($rows as $r)<div class="card row"><div><strong>{{ $r->title }}</strong><div class="muted">{{ $r->released_at?->format('d M Y') }}</div></div><a class="btn alt" href="{{ route('portal.results.show', $r->public_id) }}">{{ __('Open') }}</a></div>@empty<p class="muted">{{ __('No results to show yet.') }}</p>@endforelse
@endsection
