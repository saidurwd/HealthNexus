@extends('layouts.adminlte')

@section('page_title', 'Activity Log')

@section('page_content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Activity Log #{{ $activityLog->id }}</h3></div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Action</dt><dd class="col-sm-9">{{ $activityLog->action }}</dd>
            <dt class="col-sm-3">User</dt><dd class="col-sm-9">{{ $activityLog->user?->name ?? 'System' }}</dd>
            <dt class="col-sm-3">Entity</dt><dd class="col-sm-9">{{ $activityLog->entity_type ? class_basename($activityLog->entity_type).' #'.$activityLog->entity_id : '-' }}</dd>
            <dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $activityLog->description ?? '-' }}</dd>
            <dt class="col-sm-3">Properties</dt><dd class="col-sm-9"><pre>{{ json_encode($activityLog->properties, JSON_PRETTY_PRINT) }}</pre></dd>
            <dt class="col-sm-3">Request ID</dt><dd class="col-sm-9">{{ $activityLog->request_id ?? '-' }}</dd>
            <dt class="col-sm-3">IP Address</dt><dd class="col-sm-9">{{ $activityLog->ip_address ?? '-' }}</dd>
            <dt class="col-sm-3">User Agent</dt><dd class="col-sm-9">{{ Str::limit($activityLog->user_agent, 200) }}</dd>
            <dt class="col-sm-3">Created At</dt><dd class="col-sm-9">{{ $activityLog->created_at?->toDateTimeString() }}</dd>
        </dl>
    </div>
</div>
@stop
