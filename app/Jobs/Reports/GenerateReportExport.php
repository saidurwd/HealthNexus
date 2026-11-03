<?php

namespace App\Jobs\Reports;

use App\Models\ReportExport;
use App\Services\AuditLogger;
use App\Services\Reports\CsvExportWriter;
use App\Services\Reports\ReportContext;
use App\Services\Reports\ReportRegistry;
use App\Services\Reports\XlsxExportWriter;
use App\Services\TenantContextResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Throwable;

/** Re-establishes the requester's scope from the export row, then streams the file. A job without a scope refuses to run. */
class GenerateReportExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly int $exportId) {}

    public function handle(ReportRegistry $registry, CsvExportWriter $writer, TenantContextResolver $tenant, AuditLogger $audit): void
    {
        $export = ReportExport::query()->with('user')->findOrFail($this->exportId);

        if ($export->status !== 'queued' || ! $export->organization_id || ! $export->user) {
            return;
        }

        $previousUser = Auth::user();
        Auth::setUser($export->user);
        $tenant->setOrganizationId($export->organization_id);
        $export->branch_id ? $tenant->setBranchId($export->branch_id) : null;

        try {
            $export->update(['status' => 'running']);

            $path = sprintf('exports/%d/%s.%s', $export->organization_id, $export->public_id, $export->format);
            $disk = config('reports.disk');

            $result = ($export->format === 'xlsx' ? app(XlsxExportWriter::class) : $writer)->write(
                $registry->find($export->report_key),
                new ReportContext($export->user, $export->organization_id, $export->branch_id, $export->filters ?? []),
                $disk,
                $path,
            );

            $export->update([
                'status' => 'completed', 'disk' => $disk, 'path' => $path, 'row_count' => $result['rows'], 'checksum' => $result['checksum'],
                'completed_at' => now(), 'expires_at' => now()->addHours((int) config('reports.expiry_hours')),
            ]);

            $audit->log('EXPORT_COMPLETED', ReportExport::class, $export->id, null, ['report' => $export->report_key, 'rows' => $result['rows'], 'filters' => $export->filters]);
        } catch (Throwable $e) {
            $export->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500)]);

            throw $e;
        } finally {
            $previousUser ? Auth::setUser($previousUser) : Auth::forgetGuards();
        }
    }
}
