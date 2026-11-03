@extends('layouts.adminlte')

@section('page_title', 'Security Event')

@section('page_content')
<div class="card card-danger">
    <div class="card-header"><h3 class="card-title">Security Event #{{ $securityEvent->id }}</h3></div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">{{ __('Event') }}</dt><dd class="col-sm-9">{{ $securityEvent->event }}</dd>
            <dt class="col-sm-3">{{ __('Severity') }}</dt><dd class="col-sm-9">{{ ucfirst($securityEvent->severity) }}</dd>
            <dt class="col-sm-3">{{ __('User') }}</dt><dd class="col-sm-9">{{ $securityEvent->user?->name ?? 'Unknown' }}</dd>
            <dt class="col-sm-3">{{ __('IP Address') }}</dt><dd class="col-sm-9">{{ $securityEvent->ip_address ?? '-' }}</dd>
            <dt class="col-sm-3">{{ __('Request ID') }}</dt><dd class="col-sm-9">{{ $securityEvent->request_id ?? '-' }}</dd>
            <dt class="col-sm-3">{{ __('Properties') }}</dt><dd class="col-sm-9"><pre>{{ json_encode($securityEvent->properties, JSON_PRETTY_PRINT) }}</pre></dd>
            <dt class="col-sm-3">{{ __('Resolved At') }}</dt><dd class="col-sm-9">{{ $securityEvent->resolved_at ?? 'Not resolved' }}</dd>
            <dt class="col-sm-3">{{ __('Created At') }}</dt><dd class="col-sm-9">{{ $securityEvent->created_at?->toDateTimeString() }}</dd>
        </dl>

        @if (! $securityEvent->resolved_at && auth()->user()?->can('security.event.resolve'))
            <form method="post" action="{{ route('admin.security.resolve', $securityEvent) }}" class="border-top pt-3">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="resolution_note" class="form-label">{{ __('Resolution Note') }}</label>
                    <textarea name="resolution_note" id="resolution_note" class="form-control" rows="2"></textarea>
                </div>
                <button class="btn btn-success">{{ __('Mark Resolved') }}</button>
            </form>
        @endif
    </div>
</div>
@stop
