@extends('layouts.adminlte')

@section('title', __('Import'))

@section('content_header')
    <h1>{{ $definition->title() }} — {{ $batch->original_name }}</h1>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        @foreach([['Rows in file', $batch->total_rows], ['Ready to import', $batch->valid_rows], ['Duplicates (skipped)', $batch->duplicate_rows], ['Problems', $batch->error_rows], ['Imported', $batch->imported_rows]] as [$label, $value])
            <div class="col-6 col-md"><div class="card"><div class="card-body text-center"><div class="h3 mb-0">{{ $value }}</div><div class="text-muted small">{{ __($label) }}</div></div></div></div>
        @endforeach
    </div>

    <p>{{ __('Status:') }} <strong>{{ $batch->status }}</strong> @if($batch->error)<span class="text-danger">{{ $batch->error }}</span>@endif</p>

    <div class="mb-3">
        @if($batch->status === 'validated' && $batch->valid_rows > 0)
            <form method="POST" action="{{ route('admin.imports.commit', $batch) }}" class="d-inline">@csrf
                <button class="btn btn-success" type="submit">{{ __('Import :count rows', ['count' => $batch->valid_rows]) }}</button>
            </form>
        @endif
        @if($batch->error_rows + $batch->duplicate_rows > 0)
            <a class="btn btn-outline-secondary" href="{{ route('admin.imports.errors', $batch) }}">{{ __('Download error file') }}</a>
        @endif
        @if($batch->status === 'committed')
            <form method="POST" action="{{ route('admin.imports.rollback', $batch) }}" class="d-inline" onsubmit="return confirm('{{ __('Roll back this whole import?') }}')">@csrf
                <button class="btn btn-outline-danger" type="submit">{{ __('Roll back this import') }}</button>
            </form>
        @endif
        <a class="btn btn-link" href="{{ route('admin.imports.index') }}">{{ __('Back to imports') }}</a>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Preview (problems first)') }}</h3></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>#</th><th>{{ __('Status') }}</th><th>{{ __('Problem') }}</th>@foreach($batch->header ?? [] as $column)<th>{{ $column }}</th>@endforeach</tr></thead>
                <tbody>
                @foreach($rows as $row)
                    <tr class="@if($row->status === 'error') table-danger @elseif($row->status === 'duplicate') table-warning @endif">
                        <td>{{ $row->row_number }}</td>
                        <td>{{ $row->status }}</td>
                        <td>{{ $row->status === 'duplicate' ? __('Duplicate of :other', ['other' => $row->duplicate_of]) : implode('; ', $row->errors ?? []) }}</td>
                        @foreach($batch->header ?? [] as $column)<td>{{ $row->raw[$column] ?? '' }}</td>@endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
