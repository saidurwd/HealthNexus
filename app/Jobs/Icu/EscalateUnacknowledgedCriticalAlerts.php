<?php

namespace App\Jobs\Icu;

use App\Models\Icu\IcuCriticalAlert;
use App\Models\User;
use App\Services\Icu\IcuAlertService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EscalateUnacknowledgedCriticalAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const ESCALATION_MINUTES = 15;

    public function handle(IcuAlertService $alerts, NotificationService $notifications): void
    {
        $stale = IcuCriticalAlert::query()
            ->whereNull('acknowledged_at')
            ->where('escalation_status', IcuCriticalAlert::ESCALATION_NONE)
            ->whereIn('severity', [IcuCriticalAlert::SEVERITY_HIGH, IcuCriticalAlert::SEVERITY_CRITICAL])
            ->where('triggered_at', '<=', now()->subMinutes(self::ESCALATION_MINUTES))
            ->with('episode')
            ->get();

        foreach ($stale as $alert) {
            $alerts->escalate($alert, $alert->episode->createdBy ?? $alert->episode->attendingProvider?->user ?? $this->systemUser($alert->company_id), 'icu_team');

            $recipients = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['icu_charge_nurse', 'icu_manager', 'hospital_admin']))
                ->whereHas('companies', fn ($q) => $q->where('companies.id', $alert->company_id))
                ->get();

            if ($recipients->isNotEmpty()) {
                $notifications->send($recipients, 'icu_alert_unacknowledged', [
                    'patient' => $alert->episode->patient?->full_name ?? (string) $alert->episode->patient_id,
                    'rule' => $alert->rule,
                ]);
            }
        }
    }

    private function systemUser(int $companyId): User
    {
        return User::whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))->first();
    }
}
