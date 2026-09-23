<?php

namespace App\Services\Radiology;

use App\Events\Radiology\CriticalFindingDetected;
use App\Models\Provider;
use App\Models\Radiology\RadiologyCriticalFinding;
use App\Models\Radiology\RadiologyReport;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Critical findings are a discrete radiologist judgment call while authoring a report — not an
 * automatic numeric threshold like Laboratory's critical values (spec §46/§47).
 * Detection -> Notification -> Acknowledgement -> Audit.
 */
class RadiologyCriticalFindingService
{
    public function flag(RadiologyReport $report, string $findingText, Provider $detector): RadiologyCriticalFinding
    {
        $finding = RadiologyCriticalFinding::create([
            'company_id' => $report->company_id,
            'branch_id' => $report->branch_id,
            'report_id' => $report->id,
            'finding_text' => $findingText,
            'detected_by' => $detector->id,
            'detected_at' => now(),
            'status' => 'detected',
        ]);

        event(new CriticalFindingDetected($finding));

        return $finding->refresh();
    }

    public function acknowledge(RadiologyCriticalFinding $finding, User $user, ?string $notes = null): RadiologyCriticalFinding
    {
        if ($finding->isAcknowledged()) {
            throw ValidationException::withMessages(['finding' => 'This critical finding has already been acknowledged.']);
        }

        $finding->update([
            'acknowledged_by' => $user->id,
            'acknowledged_at' => now(),
            'status' => 'acknowledged',
            'notes' => $notes ?? $finding->notes,
        ]);

        return $finding->refresh();
    }
}
