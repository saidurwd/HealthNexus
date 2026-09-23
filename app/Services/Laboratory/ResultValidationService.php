<?php

namespace App\Services\Laboratory;

use App\Models\Laboratory\LabResult;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Result Entry -> Technical Review -> Validation -> Pathologist Approval -> Report (spec §28).
 * Not every test requires pathologist approval — gated per-test via
 * LabTest::requires_pathologist_approval (spec §29: "make workflow configurable").
 */
class ResultValidationService
{
    public function technicalValidate(LabResult $result, User $validator): LabResult
    {
        if ($result->result_status !== 'entered') {
            throw ValidationException::withMessages(['result' => "Cannot technically validate a result in '{$result->result_status}' status."]);
        }

        if ((int) $result->entered_by === $validator->id) {
            throw ValidationException::withMessages(['result' => 'A result cannot be technically validated by the same user who entered it.']);
        }

        $result->update([
            'result_status' => 'technically_validated',
            'technical_validated_by' => $validator->id,
            'technical_validated_at' => now(),
        ]);

        $result = $result->refresh();

        if (! $result->test->requires_pathologist_approval) {
            $this->finalizeValidation($result);
        }

        return $result->refresh();
    }

    public function pathologistApprove(LabResult $result, User $pathologist): LabResult
    {
        return DB::transaction(function () use ($result, $pathologist) {
            if ($result->result_status !== 'technically_validated') {
                throw ValidationException::withMessages(['result' => "Cannot approve a result in '{$result->result_status}' status."]);
            }

            $result->update([
                'result_status' => 'pathologist_validated',
                'pathologist_approved_by' => $pathologist->id,
                'pathologist_approved_at' => now(),
            ]);

            $this->finalizeValidation($result->refresh());

            return $result->refresh();
        });
    }

    /**
     * Marks the parent LabOrderItem validated and, once every item on the order has reached
     * validation, moves the LabOrder itself into 'validated' (ready for report finalization).
     */
    private function finalizeValidation(LabResult $result): void
    {
        $item = $result->orderItem;
        $item->update(['status' => 'validated']);

        $order = $item->labOrder;
        $allValidated = $order->items()
            ->where('status', '!=', 'cancelled')
            ->whereNotIn('status', ['validated'])
            ->doesntExist();

        if ($allValidated && $order->status === 'awaiting_validation') {
            app(LabOrderLifecycleService::class)->transitionTo($order, 'validated');
        }
    }
}
