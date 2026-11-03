<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Export\PageExporter;
use App\Services\Export\PageTables;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Any staff screen can be taken away as Excel or PDF by adding ?export=xlsx or ?export=pdf to its address.
 * The screen runs as it always does (same rights, same tenant, same filters), so nobody can export what they cannot see;
 * a paged list is followed page by page up to the limits in config/export.php, and every export is written to the audit trail.
 */
class ExportPage
{
    public function __construct(
        private readonly PageTables $reader,
        private readonly PageExporter $exporter,
        private readonly AuditLogger $audit,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $format = $request->query('export');
        $user = $request->user();
        $name = (string) $request->route()?->getName();

        if (! in_array($format, ['xlsx', 'pdf'], true) || ! $request->isMethod('GET') || ! $user instanceof User || ! str_starts_with($name, 'admin.') || Str::is((array) config('export.except', []), $name)) {
            return $next($request);
        }

        $key = 'export:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, (int) config('export.per_minute', 20))) {
            abort(429, __('Too many exports. Please wait a minute.'));
        }
        RateLimiter::hit($key, 60);

        $filters = $request->except(['export', 'page']);
        $request->query->remove('export');

        $first = $next($request);
        if ($first->getStatusCode() !== 200 || ! str_contains((string) $first->headers->get('Content-Type'), 'text/html')) {
            return $first;
        }

        $read = $this->reader->read((string) $first->getContent());
        $tables = $read['tables'];
        $rows = array_sum(array_map(fn ($t) => count($t['rows']), $tables));
        $maxPages = min($read['pages'], (int) config('export.max_pages', 40));
        $maxRows = (int) config('export.max_rows', 20000);

        for ($page = 2; $page <= $maxPages && $rows < $maxRows; $page++) {
            $request->query->set('page', (string) $page);
            $more = $next($request);
            if ($more->getStatusCode() !== 200) {
                break;
            }
            foreach ($this->reader->read((string) $more->getContent())['tables'] as $i => $more) {
                if (isset($tables[$i]) && $tables[$i]['header'] === $more['header']) {
                    $tables[$i]['rows'] = array_merge($tables[$i]['rows'], $more['rows']);
                    $rows += count($more['rows']);
                }
            }
        }
        $rows = array_sum(array_map(fn ($t) => count($t['rows']), $tables));
        $tables = array_map(fn ($t) => $t['rows'] === [] ? $t : array_merge($t, ['rows' => array_slice($t['rows'], 0, $maxRows)]), $tables);

        $title = $read['title'] !== '' ? $read['title'] : $name;
        $this->audit->log('EXPORT', null, null, null, ['route' => $name, 'path' => '/'.$request->path(), 'format' => $format, 'tables' => count($tables), 'rows' => $rows, 'filters' => $filters], $request);

        $bytes = $format === 'xlsx' ? $this->exporter->xlsx($title, $tables, $user, $filters) : $this->exporter->pdf($title, $tables, $user, $filters);

        return response($bytes, 200, [
            'Content-Type' => $format === 'xlsx' ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->exporter->filename($title, $format).'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
