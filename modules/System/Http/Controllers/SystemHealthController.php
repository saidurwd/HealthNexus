<?php

namespace Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SystemHealthService;

class SystemHealthController extends Controller
{
    public function index(SystemHealthService $health)
    {
        $checks = $health->check();

        return view('admin.system.health', compact('checks'));
    }
}
