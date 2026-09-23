<?php

namespace App\Services\Laboratory;

use App\Events\Laboratory\CriticalResultDetected;
use App\Models\Laboratory\LabCriticalValue;
use App\Models\Laboratory\LabOrderItem;
use App\Models\Laboratory\LabResult;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Writes the correct value column per the test's result_type (spec §26 — never forces every
 * result into a single numeric column), auto-derives the reference-range flag (spec §27 — never
 * relies on manual-only flagging), and checks configured critical values (spec §14).
 */
class ResultEntryService
{
    public function __construct(
        private readonly LabReferenceRangeResolver $ranges,
        private readonly LabOrderLifecycleService $lifecycle,
    ) {}

    /**
     * @param  array{numeric_value?:string|null,text_value?:string|null,qualitative_value?:string|null,unit?:string|null}  $data
     */
    public function enter(LabOrderItem $item, array $data, User $user): LabResult
    {
        return DB::transaction(function () use ($item, $data, $user) {
            if ($item->isUnmatched()) {
                throw ValidationException::withMessages(['item' => 'This order item has no matched test catalog entry — resolve it before entering a result.']);
            }

            if (! in_array($item->status, ['received', 'processing', 'resulted'], true)) {
                throw ValidationException::withMessages(['item' => "Cannot enter a result for an item in '{$item->status}' status."]);
            }

            $test = $item->test;
            $order = $item->labOrder;
            $patient = $order->patient;

            $range = $this->ranges->resolve($test, $patient);

            $abnormalFlag = match ($test->test_type) {
                'quantitative', 'semi_quantitative', 'calculated' => $this->ranges->flagFor($data['numeric_value'] ?? null, $range),
                default => $this->qualitativeFlag($data['qualitative_value'] ?? null),
            };

            $criticalFlag = $this->isCritical($test->id, $data['numeric_value'] ?? null);

            if ($criticalFlag) {
                $abnormalFlag = bccomp((string) $data['numeric_value'], (string) ($range['low'] ?? $data['numeric_value']), 4) === -1
                    ? 'critical_low'
                    : 'critical_high';
            }

            $result = LabResult::create([
                'company_id' => $order->company_id,
                'branch_id' => $order->branch_id,
                'lab_order_item_id' => $item->id,
                'test_id' => $test->id,
                'specimen_id' => $item->specimen_id,
                'result_type' => $test->test_type,
                'numeric_value' => $data['numeric_value'] ?? null,
                'text_value' => $data['text_value'] ?? null,
                'qualitative_value' => $data['qualitative_value'] ?? null,
                'unit' => $data['unit'] ?? $range['unit'] ?? $test->unit,
                'reference_range_low' => $range['low'] ?? null,
                'reference_range_high' => $range['high'] ?? null,
                'reference_range_text' => $range['text'] ?? null,
                'abnormal_flag' => $abnormalFlag,
                'critical_flag' => $criticalFlag,
                'result_status' => 'entered',
                'entered_by' => $user->id,
                'entered_at' => now(),
                'version' => 1,
                'is_current' => true,
            ]);

            $item->update([
                'status' => 'resulted',
                'result_status' => 'completed',
                'completed_at' => now(),
            ]);

            $this->syncOrderStatus($order);

            if ($criticalFlag) {
                event(new CriticalResultDetected($result));
            }

            return $result;
        });
    }

    private function qualitativeFlag(?string $value): string
    {
        if ($value === null) {
            return 'not_applicable';
        }

        return match (mb_strtolower($value)) {
            'positive', 'reactive' => 'positive',
            'negative', 'non-reactive', 'non reactive' => 'negative',
            'normal' => 'normal',
            default => 'abnormal',
        };
    }

    private function isCritical(int $testId, ?string $numericValue): bool
    {
        if ($numericValue === null) {
            return false;
        }

        $critical = LabCriticalValue::query()->where('test_id', $testId)->where('is_active', true)->first();

        if (! $critical) {
            return false;
        }

        if ($critical->low_threshold !== null && bccomp($numericValue, (string) $critical->low_threshold, 4) === -1) {
            return true;
        }

        if ($critical->high_threshold !== null && bccomp($numericValue, (string) $critical->high_threshold, 4) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Recomputes the order's status from its items' progress and walks the lifecycle's
     * transition graph one valid hop at a time until it reaches the target — 'received' can't
     * jump straight to 'awaiting_validation', it must pass through 'processing' first.
     */
    private function syncOrderStatus($order): void
    {
        $items = $order->items()->where('status', '!=', 'cancelled')->get();
        $resulted = $items->where('status', 'resulted')->count();

        if ($resulted === 0) {
            return;
        }

        $target = $resulted === $items->count() ? 'awaiting_validation' : 'partial_result';

        $path = match ($order->status) {
            'received' => $target === 'awaiting_validation' ? ['processing', 'awaiting_validation'] : ['processing', 'partial_result'],
            'processing' => [$target],
            'partial_result' => $target === 'awaiting_validation' ? ['awaiting_validation'] : [],
            default => [],
        };

        foreach ($path as $status) {
            $order = $this->lifecycle->transitionTo($order, $status);
        }
    }
}
