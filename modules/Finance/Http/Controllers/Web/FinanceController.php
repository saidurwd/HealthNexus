<?php

namespace Modules\Finance\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\TenantContextResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Shared guards for the finance screens: a record of another hospital does not exist (404); a missing permission is 403. */
abstract class FinanceController extends Controller
{
    public function __construct(protected readonly TenantContextResolver $tenant) {}

    protected function allow(Request $request, string $permission): void
    {
        abort_unless($request->user()->can($permission), 403);
    }

    protected function organizationId(): int
    {
        return (int) $this->tenant->getOrganizationId();
    }

    /**
     * @template T of Model
     *
     * @param  class-string<T>  $class
     * @return T
     */
    protected function find(string $class, string $publicId): Model
    {
        return $class::query()->where('organization_id', $this->organizationId())->where('public_id', $publicId)->firstOrFail();
    }

    /** A date range from the query string, defaulting to the current month; the end is never before the start. @return array{0:string,1:string} */
    protected function range(Request $request): array
    {
        $r = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);
        $to = $r['to'] ?? now()->setTimezone((string) config('finance.timezone', 'Asia/Dhaka'))->toDateString();
        $from = $r['from'] ?? date('Y-m-01', strtotime($to));

        return [$from, max($from, $to)];
    }

    /** Download rows as CSV; needs the separate export permission. Cells that start with a formula character are quoted so a spreadsheet does not run them. */
    protected function csv(Request $request, string $name, array $header, iterable $rows): StreamedResponse
    {
        $this->allow($request, 'finance.report.export');
        $safe = fn ($v) => is_string($v) && $v !== '' && in_array($v[0], ['=', '+', '-', '@'], true) && ! is_numeric($v) ? "'".$v : $v;

        return response()->streamDownload(function () use ($header, $rows, $safe) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($rows as $r) {
                fputcsv($out, array_map($safe, (array) $r));
            }
            fclose($out);
        }, $name.'-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
