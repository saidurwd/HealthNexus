@extends('layouts.adminlte')

@section('page_title', 'File')

@section('page_content')
<div class="card">
    <div class="card-header"><h3 class="card-title">{{ $file->original_name }}</h3></div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Original Name</dt><dd class="col-sm-9">{{ $file->original_name }}</dd>
            <dt class="col-sm-3">MIME Type</dt><dd class="col-sm-9">{{ $file->mime_type }}</dd>
            <dt class="col-sm-3">Size</dt><dd class="col-sm-9">{{ number_format($file->size / 1024, 2) }} KB</dd>
            <dt class="col-sm-3">Checksum (SHA-256)</dt><dd class="col-sm-9">{{ Str::limit($file->checksum, 32) }}</dd>
            <dt class="col-sm-3">Uploaded By</dt><dd class="col-sm-9">{{ $file->uploader?->name ?? 'Unknown' }}</dd>
            <dt class="col-sm-3">Uploaded At</dt><dd class="col-sm-9">{{ $file->created_at?->toDateTimeString() }}</dd>
        </dl>
        <a href="{{ route('admin.files.download', $file) }}" class="btn btn-primary">Download</a>
        <form method="post" action="{{ route('admin.files.destroy', $file) }}" class="d-inline">
            @csrf @method('DELETE')
            <button class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>
@stop
