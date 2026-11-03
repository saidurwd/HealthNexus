<?php

namespace App\Listeners\Welfare;

use App\Events\Billing\AdjustmentApproved;
use App\Services\Welfare\ApplicationService;
use Throwable;

/** Billing approved a discount: if it came from a welfare grant, charge the fund. A failure never reaches the cashier; `welfare:check` puts it right. */
class SettleWelfareApplication
{
    public function __construct(private readonly ApplicationService $applications) {}

    public function handle(AdjustmentApproved $event): void
    {
        try {
            $this->applications->settle($event->adjustment);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
