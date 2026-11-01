@extends('layouts.adminlte')

@section('page_title', __('Documents to read'))

@section('page_content')
    @include('admin.quality._flash')
    <p class="small text-muted">{{ __('Read each document and press the button to say you have read and understood it.') }}</p>
    <div class="list-group">@forelse($docs as $d)<a class="list-group-item list-group-item-action d-flex justify-content-between" href="{{ route('admin.quality.documents.show', $d->public_id) }}"><span><strong>{{ $d->code }}</strong> · {{ $d->title }}</span><span class="text-muted small">v{{ $d->current->version }} · {{ $d->current->effective_on?->format('d M Y') }}</span></a>@empty<div class="list-group-item text-muted">{{ __('You have read everything you were asked to.') }}</div>@endforelse</div>
@stop
