@extends('layouts.adminlte')

@section('page_title', __('Codes other systems understand'))

@section('page_content')
    @include('admin.interop._flash')
    <p class="text-muted small">{{ __('This is the code registry as outside systems see it. We keep no second list of codes.') }}</p>
    <form method="GET" class="card"><div class="card-body row g-2"><div class="col-md-4"><label class="form-label" for="system">{{ __('Code system') }}</label><select id="system" name="system" class="form-select">@foreach($systems as $s)<option value="{{ $s->key }}" @selected(request('system') === $s->key)>{{ $s->name }} ({{ $s->key }})</option>@endforeach</select></div><div class="col-md-4"><label class="form-label" for="code">{{ __('Code') }}</label><input id="code" name="code" class="form-control" value="{{ request('code') }}" required></div><div class="col-md-4 d-flex align-items-end"><button class="btn btn-primary">{{ __('Look up') }}</button></div></div></form>
    @if(request('code'))<div class="card"><div class="card-body">@if($found)<strong>{{ $found['code'] }}</strong> — {{ $found['display'] }} @if($found['display_bn'])<span class="text-muted">/ {{ $found['display_bn'] }}</span>@endif<div class="small text-muted">{{ $found['system'] }}</div>@else<span class="text-muted">{{ __('That code is not in the registry.') }}</span>@endif</div>
        @if($maps)<ul class="list-group list-group-flush small">@foreach($maps as $m)<li class="list-group-item">{{ __('Same as') }} <strong>{{ $m['code'] }}</strong> <span class="text-muted">{{ $m['system'] }} · {{ $m['relationship'] }}</span></li>@endforeach</ul>@endif</div>@endif
@stop
