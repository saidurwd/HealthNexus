@extends('layouts.adminlte')

@section('page_title', 'Queue Monitor')

@section('page_content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Queue Monitor</h3></div>
    <div class="card-body">
        <table class="table table-sm w-auto">
            <tr><th class="pe-4">Connection</th><td>{{ $connection }}</td></tr>
            @if($pendingCount !== null)
                <tr><th class="pe-4">Pending Jobs</th><td>{{ $pendingCount }}</td></tr>
            @endif
            <tr><th class="pe-4">Failed Jobs</th><td>{{ $failedJobs->count() }}</td></tr>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Recent Failed Jobs</h3></div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>UUID</th>
                    <th>Connection</th>
                    <th>Queue</th>
                    <th>Failed At</th>
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
                    <tr><td colspan="4" class="text-center">No failed jobs.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@stop
