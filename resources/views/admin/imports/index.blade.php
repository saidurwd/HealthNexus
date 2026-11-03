@extends('layouts.adminlte')

@section('title', __('Data import'))

@section('content_header')
    <h1>{{ __('Data import') }}</h1>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Upload a file') }}</h3></div>
        <form method="POST" action="{{ route('admin.imports.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('What are you importing?') }}</label>
                    <select name="import" class="form-select" required>
                        @foreach($definitions as $definition)
                            <option value="{{ $definition->key() }}">{{ $definition->title() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">{{ __('CSV file') }}</label>
                    <input type="file" name="file" accept=".csv,text/csv" class="form-control @error('file') is-invalid @enderror" required>
                    @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary" type="submit">{{ __('Upload and check') }}</button>
                </div>
            </div>
        </form>
        <div class="card-footer">
            {{ __('Download a template:') }}
            @foreach($definitions as $definition)
                <a class="me-2" href="{{ route('admin.imports.template', $definition->key()) }}">{{ $definition->title() }}</a>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Recent imports') }}</h3></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>{{ __('File') }}</th><th>{{ __('Type') }}</th><th>{{ __('Status') }}</th><th>{{ __('Rows') }}</th><th>{{ __('Date') }}</th></tr></thead>
                <tbody>
                @forelse($batches as $batch)
                    <tr>
                        <td><a href="{{ route('admin.imports.show', $batch) }}">{{ $batch->original_name }}</a></td>
                        <td>{{ $batch->import_key }}</td>
                        <td>{{ $batch->status }}</td>
                        <td>{{ $batch->imported_rows }} / {{ $batch->total_rows }}</td>
                        <td>{{ $batch->created_at->format('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">{{ __('No imports yet.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
