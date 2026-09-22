<?php

namespace Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class QueueMonitorController extends Controller
{
    public function index()
    {
        $connection = config('queue.default');

        $pendingCount = config('queue.connections.'.$connection.'.driver') === 'database'
            ? DB::table('jobs')->count()
            : null;

        $failedJobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(100)
            ->get();

        return view('admin.system.queue', compact('connection', 'pendingCount', 'failedJobs'));
    }
}
