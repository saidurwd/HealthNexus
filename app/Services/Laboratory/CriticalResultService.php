<?php

namespace App\Services\Laboratory;

use App\Models\Laboratory\LabCriticalResultAlert;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CriticalResultService
{
    public function acknowledge(LabCriticalResultAlert $alert, User $user, ?string $notes = null): LabCriticalResultAlert
    {
        if ($alert->isAcknowledged()) {
            throw ValidationException::withMessages(['alert' => 'This critical result alert has already been acknowledged.']);
        }

        $alert->update([
            'acknowledged_by' => $user->id,
            'acknowledged_at' => now(),
            'notes' => $notes ?? $alert->notes,
        ]);

        return $alert->refresh();
    }
}
