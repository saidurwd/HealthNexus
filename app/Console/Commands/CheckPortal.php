<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Crm\ComplaintService;
use App\Services\ModuleManager;
use App\Services\Portal\CommunicationService;
use App\Services\Portal\PortalPaymentService;
use App\Services\Portal\PortalRetentionService;
use App\Services\Portal\ProxyService;
use App\Services\Portal\ResultReleaseService;
use Illuminate\Console\Command;

/** Housekeeping for the patient portal and CRM: new reports get a release decision, timed releases happen, held texts go out after quiet hours, payments in progress are re-checked, old codes are removed. */
class CheckPortal extends Command
{
    protected $signature = 'portal:check {--since-days= : bring in reports from this many days back (an old backlog), instead of the usual window}';

    protected $description = 'Release timed results, send held messages, re-check online payments, expire relatives\' approvals, escalate late complaints, remove old codes';

    public function handle(ResultReleaseService $release, CommunicationService $comms, PortalPaymentService $payments, ProxyService $proxies, ComplaintService $complaints, PortalRetentionService $retention, ModuleManager $modules): int
    {
        foreach (Organization::query()->where('is_active', true)->get() as $organization) {
            if (! $modules->isEnabled('portal', $organization->id)) {
                continue;
            }
            $made = $release->sync($organization->id, 1000, $this->option('since-days') !== null ? (int) $this->option('since-days') : null);
            $due = $release->releaseDue();
            $sent = $comms->releaseDeferred();
            $paid = $payments->sweep();
            $expired = $proxies->expireDue();
            $late = $complaints->escalateOverdue();
            $this->info("{$organization->code}: $made report(s) given a release decision, $due released on time, $sent held message(s) sent, {$paid['confirmed']} payment(s) confirmed, {$paid['expired']} expired, $expired approval(s) ended, $late complaint(s) escalated.");
        }
        $purged = $retention->purge();
        $this->info("Removed {$purged['otps']} old code(s) and {$purged['events']} old sign-in event(s).");

        return self::SUCCESS;
    }
}
