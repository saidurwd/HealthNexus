<?php

namespace App\Services\Reports;

use App\Jobs\Reports\GenerateReportExport;
use App\Models\ReportExport;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContextResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    public function __construct(
        private readonly ReportRegistry $registry,
        private readonly AuditLogger $audit,
        private readonly TenantContextResolver $tenant,
    ) {}

    /** Validates, authorises and queues. Nothing is read from the database here; the job does the work. */
    public function request(User $user, string $reportKey, string $format, array $filters = []): ReportExport
    {
        $definition = $this->registry->find($reportKey);

        if (! $user->can($definition->permission())) {
            throw new AuthorizationException('You are not allowed to export this report.');
        }

        if (! in_array($format, config('reports.formats'), true)) {
            throw ValidationException::withMessages(['format' => "Format [$format] is not available."]);
        }

        $organizationId = $this->tenant->getOrganizationId()
            ?? throw ValidationException::withMessages(['organization' => 'No active organization.']);

        $filters = Validator::make($filters, $definition->filterRules())->validate();

        $export = ReportExport::create([
            'organization_id' => $organizationId, 'branch_id' => $this->tenant->getBranchId(), 'user_id' => $user->id,
            'report_key' => $reportKey, 'format' => $format, 'filters' => $filters, 'status' => 'queued', 'requested_at' => now(),
        ]);

        $this->audit->log('EXPORT_REQUESTED', ReportExport::class, $export->id, null, ['report' => $reportKey, 'format' => $format, 'filters' => $filters]);

        GenerateReportExport::dispatch($export->id);

        return $export;
    }

    /** Owner, or a holder of reporting.export in the same organization, may download a completed, unexpired file. */
    public function download(ReportExport $export, User $user): StreamedResponse
    {
        $allowed = $export->user_id === $user->id || ($user->can('reporting.export') && $export->organization_id === $this->tenant->getOrganizationId());

        if (! $allowed) {
            throw new AuthorizationException('This export belongs to someone else.');
        }

        abort_unless($export->isDownloadable(), 410, 'This export has expired.');

        $export->increment('download_count');
        $export->update(['last_downloaded_at' => now()]);
        $this->audit->log('EXPORT_DOWNLOADED', ReportExport::class, $export->id, null, ['report' => $export->report_key]);

        return Storage::disk($export->disk)->download($export->path, $export->report_key.'-'.$export->public_id.'.'.$export->format);
    }

    /** Deletes files past their expiry. @return int exports expired */
    public function pruneExpired(): int
    {
        $count = 0;

        ReportExport::query()->where('status', 'completed')->where('expires_at', '<=', now())->each(function (ReportExport $export) use (&$count) {
            Storage::disk($export->disk)->delete($export->path);
            $export->update(['status' => 'expired', 'path' => null]);
            $count++;
        });

        return $count;
    }
}
