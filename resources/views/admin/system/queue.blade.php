@extends('layouts.adminlte')

@section('page_title', 'Queue Monitor')

@section('page_content')
<div class="card">
    <div class="card-header"><h3 class="card-title">{{ __('Queue Monitor') }}</h3></div>
    <div class="card-body">
        <table class="table table-sm w-auto">
            <tr><th class="pe-4">{{ __('Connection') }}</th><td>{{ $connection }}</td></tr>
            @if($pendingCount !== null)
                <tr><th class="pe-4">{{ __('Pending Jobs') }}</th><td>{{ $pendingCount }}</td></tr>
            @endif
            <tr><th class="pe-4">{{ __('Failed Jobs') }}</th><td>{{ $failedJobs->count() }}</td></tr>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">{{ __('Recent Failed Jobs') }}</h3></div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>{{ __('UUID') }}</th>
                    <th>{{ __('Connection') }}</th>
                    <th>{{ __('Queue') }}</th>
                    <th>{{ __('Failed At') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($failedJobs as $job)
                    <tr>
                        <td><code>{{ $job->uuid }}</code></td>
                        <td>{{ $job->connection }}</td>
                        <td>{{ $job->queue }}</td>
                        <td>{{ $job->failed_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">{{ __('No failed jobs.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@stop
