@extends('portal.layout')
@section('title', __('Notices'))
@section('content')
<h1>{{ __('Notices') }}</h1>
@forelse($rows as $n)<div class="card"><div class="row"><strong>{{ $n->title }}</strong><span class="muted">{{ $n->created_at->format('d M H:i') }}</span></div>@if($n->body)<div>{{ $n->body }}</div>@endif @if($n->link)<a href="{{ $n->link }}">{{ __('Open') }}</a>@endif</div>@empty<p class="muted">{{ __('Nothing new.') }}</p>@endforelse
{{ $rows->links() }}
@endsection
