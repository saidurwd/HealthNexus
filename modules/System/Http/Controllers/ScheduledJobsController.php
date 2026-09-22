<?php

namespace Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;

class ScheduledJobsController extends Controller
{
    public function index()
    {
        Artisan::call('schedule:list');
        $output = Artisan::output();

        return view('admin.system.scheduled-jobs', compact('output'));
    }
}
