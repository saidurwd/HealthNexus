<?php

namespace App\Jobs\Clinical;

use App\Services\Clinical\Alerts\AlertService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * System sweep: carries no tenant scope of its own because each alert row carries its organization, and the
 * service only ever notifies users of that alert's organization.
 */
class EscalateUnacknowledgedAlerts implements ShouldQueue
{
    use Queueable;

    public function handle(AlertService $alerts): void
    {
        $alerts->escalateDue();
    }
}
