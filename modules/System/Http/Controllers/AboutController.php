<?php

namespace Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class AboutController extends Controller
{
    public function index()
    {
        $info = [
            'app_name' => config('app.name'),
            'app_env' => app()->environment(),
            'app_debug' => config('app.debug'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'database_driver' => config('database.default'),
            'database_version' => $this->databaseVersion(),
            'cache_store' => config('cache.default'),
            'queue_connection' => config('queue.default'),
            'session_driver' => config('session.driver'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
        ];

        return view('admin.system.about', compact('info'));
    }

    private function databaseVersion(): ?string
    {
        try {
            return DB::selectOne('select version() as version')->version ?? null;
        } catch (\Throwable) {
            return null;
        }
    }
}
