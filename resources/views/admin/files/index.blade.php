@extends('layouts.adminlte')

@section('page_title', 'My Files')

@section('page_content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">My Files</h3>
        <div class="card-tools">
            <form method="get" action="{{ route('admin.files.index') }}" class="input-group input-group-sm" style="width: 250px;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search files">
                <button class="btn btn-primary">Go</button>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Size</th>
                    <th>Type</th>
                    <th>Uploaded</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($files as $file)
                    <tr>
                        <td>{{ $file->original_name }}</td>
                        <td>{{ number_format($file->size / 1024, 1) }} KB</td>
                        <td>{{ $file->mime_type }}</td>
                        <td>{{ $file->created_at?->format('Y-m-d H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.files.download', $file) }}" class="btn btn-sm btn-outline-primary">Download</a>
                            <form method="post" action="{{ route('admin.files.destroy', $file) }}" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No files found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $files->links() }}</div>
</div>
@stop
