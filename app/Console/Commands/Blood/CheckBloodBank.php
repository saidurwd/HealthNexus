<?php

namespace App\Console\Commands\Blood;

use App\Models\Organization;
use App\Services\Blood\BloodInventoryService;
use App\Services\Blood\BloodIssueService;
use Illuminate\Console\Command;

/** Hourly housekeeping: expire old units, release lapsed reservations, warn about near-expiry units. */
class CheckBloodBank extends Command
{
    protected $signature = 'blood:check';

    protected $description = 'Expire blood units past their date, release lapsed reservations, warn about units expiring soon';

    public function handle(BloodInventoryService $inventory, BloodIssueService $issues): int
    {
        $expired = $inventory->expireDue();
        $released = $issues->releaseLapsedReservations();
        $warned = Organization::query()->where('is_active', true)->get()->sum(fn ($o) => $inventory->warnNearExpiry($o->id));
        $this->info("Expired $expired unit(s), released $released reservation(s), $warned unit(s) expiring soon.");

        return self::SUCCESS;
    }
}
