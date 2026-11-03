@extends('adminlte::page')

@section('title', 'Diagnosis Statistics')

@section('content_header')
    <h1>{{ __('Diagnosis Statistics') }}</h1>
@stop

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="form-inline">
                <label class="mr-2">{{ __('From') }}</label>
                <input type="date" name="from" value="{{ $from }}" class="form-control mr-3">
                <label class="mr-2">{{ __('To') }}</label>
                <input type="date" name="to" value="{{ $to }}" class="form-control mr-3">
                <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                <a href="{{ route('admin.reports.clinical') }}" class="btn btn-outline-secondary ml-2">{{ __('Back to Clinical Reports') }}</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Top Diagnoses ({{ $from }} — {{ $to }})</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>{{ __('Diagnosis') }}</th>
                        <th>{{ __('Coding System') }}</th>
                        <th>{{ __('Count') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($diagnoses as $row)
                        <tr>
                            <td>{{ $row->description }}</td>
                            <td>{{ $row->coding_system ?? '—' }}</td>
                            <td>{{ $row->aggregate }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">{{ __('No data for this range.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
