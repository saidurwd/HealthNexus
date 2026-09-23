<?php

namespace App\Services\Radiology;

use App\Events\Radiology\RadiologyExamCompleted;
use App\Models\Radiology\RadiologyExamination;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records timestamps at every major transition (spec §27) — these back the TAT reporting.
 */
class RadiologyExaminationService
{
    public function __construct(private readonly RadiologyOrderLifecycleService $lifecycle) {}

    public function checkIn(RadiologyExamination $examination, User $user): RadiologyExamination
    {
        if ($examination->status !== 'scheduled') {
            throw ValidationException::withMessages(['examination' => "Cannot check in an examination in '{$examination->status}' status."]);
        }

        $examination->update(['status' => 'checked_in', 'check_in_at' => now()]);
        $this->lifecycle->transitionTo($examination->orderItem->radiologyOrder, 'checked_in');
        $examination->orderItem->update(['status' => 'checked_in']);

        return $examination->refresh();
    }

    public function startPreparation(RadiologyExamination $examination): RadiologyExamination
    {
        if ($examination->status !== 'checked_in') {
            throw ValidationException::withMessages(['examination' => "Cannot start preparation for an examination in '{$examination->status}' status."]);
        }

        $examination->update(['status' => 'preparing']);
        $this->lifecycle->transitionTo($examination->orderItem->radiologyOrder, 'preparing');

        return $examination->refresh();
    }

    public function start(RadiologyExamination $examination, User $user): RadiologyExamination
    {
        return DB::transaction(function () use ($examination, $user) {
            if (! in_array($examination->status, ['checked_in', 'preparing'], true)) {
                throw ValidationException::withMessages(['examination' => "Cannot start an examination in '{$examination->status}' status."]);
            }

            $order = $examination->orderItem->radiologyOrder;

            if (in_array($order->status, ['checked_in', 'preparing'], true)) {
                $this->lifecycle->transitionTo($order, 'ready');
            }

            $examination->update(['status' => 'in_progress', 'started_at' => now()]);
            $this->lifecycle->transitionTo($order->fresh(), 'in_progress');
            $examination->orderItem->update(['status' => 'in_progress']);

            return $examination->refresh();
        });
    }

    public function complete(RadiologyExamination $examination, User $user, array $data = []): RadiologyExamination
    {
        return DB::transaction(function () use ($examination, $user, $data) {
            if ($examination->status !== 'in_progress') {
                throw ValidationException::withMessages(['examination' => "Cannot complete an examination in '{$examination->status}' status."]);
            }

            $examination->update([
                'status' => 'completed',
                'completed_at' => now(),
                'technical_notes' => $data['technical_notes'] ?? $examination->technical_notes,
            ]);

            $order = $examination->orderItem->radiologyOrder;
            $this->lifecycle->transitionTo($order, 'completed');
            $examination->orderItem->update(['status' => 'completed', 'result_status' => 'completed']);

            event(new RadiologyExamCompleted($examination->refresh()));

            return $examination->fresh();
        });
    }
}
