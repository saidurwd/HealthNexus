<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Services\SettingsService;
use Illuminate\Console\Command;

class PruneAuditLogs extends Command
{
    protected $signature = 'audit:prune';

    protected $description = 'Delete audit and activity log rows older than the configured retention period (audit.retention_days)';

    public function handle(SettingsService $settings): int
    {
        $retentionDays = (int) $settings->get('audit.retention_days', 365);

        if ($retentionDays <= 0) {
            $this->info('Audit retention is disabled (audit.retention_days <= 0) — nothing to prune.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($retentionDays);

        $auditDeleted = AuditLog::where('created_at', '<', $cutoff)->delete();
        $activityDeleted = ActivityLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Pruned {$auditDeleted} audit log(s) and {$activityDeleted} activity log(s) older than {$retentionDays} days.");

        return self::SUCCESS;
    }
}
