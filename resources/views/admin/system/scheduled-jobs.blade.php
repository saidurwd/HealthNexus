@extends('layouts.adminlte')

@section('page_title', 'Scheduled Jobs')

@section('page_content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Scheduled Jobs</h3></div>
    <div class="card-body">
        <pre class="mb-0">{{ $output }}</pre>
    </div>
    <div class="card-footer text-muted small">
        Output of <code>php artisan schedule:list</code>. This only reflects what is registered
        in <code>routes/console.php</code> — it does not confirm the server's cron entry is
        actually invoking <code>schedule:run</code> every minute (see System Health for that).
    </div>
</div>
@stop
