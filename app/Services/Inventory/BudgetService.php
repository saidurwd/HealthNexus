<?php

namespace App\Services\Inventory;

use App\Models\Inventory\ProcurementBudget;
use App\Models\Inventory\ProcurementPurchaseOrder;
use Illuminate\Validation\ValidationException;

/** Department purchasing budgets (S10). A department without a budget row is not limited; with one, committed orders cannot pass it. */
class BudgetService
{
    public function set(int $organizationId, int $departmentId, string $periodStart, string $periodEnd, string $amount): ProcurementBudget
    {
        if ($periodEnd < $periodStart) {
            throw ValidationException::withMessages(['period_end' => 'The period ends before it starts.']);
        }

        $budget = ProcurementBudget::query()->where(['organization_id' => $organizationId, 'department_id' => $departmentId])->whereDate('period_start', $periodStart)->first();
        if ($budget) {
            $budget->update(['period_end' => $periodEnd, 'amount' => $amount]);

            return $budget->refresh();
        }

        return ProcurementBudget::create(['organization_id' => $organizationId, 'department_id' => $departmentId, 'period_start' => $periodStart, 'period_end' => $periodEnd, 'amount' => $amount]);
    }

    public function current(int $organizationId, int $departmentId, ?string $on = null): ?ProcurementBudget
    {
        $on ??= now()->toDateString();

        return ProcurementBudget::query()->where(['organization_id' => $organizationId, 'department_id' => $departmentId])->whereDate('period_start', '<=', $on)->whereDate('period_end', '>=', $on)->first();
    }

    /** Value of live orders (not draft, not cancelled) in the budget's period. */
    public function committed(ProcurementBudget $budget, ?int $exceptOrderId = null): string
    {
        return bcadd((string) ProcurementPurchaseOrder::query()->where(['organization_id' => $budget->organization_id, 'department_id' => $budget->department_id])
            ->whereNotIn('status', ['draft', 'cancelled'])->whereDate('order_date', '>=', $budget->period_start->toDateString())->whereDate('order_date', '<=', $budget->period_end->toDateString())
            ->when($exceptOrderId, fn ($q) => $q->where('id', '!=', $exceptOrderId))->sum('total'), '0', 2);
    }

    public function assertWithin(int $organizationId, ?int $departmentId, string $newTotal, ?int $exceptOrderId = null, ?string $on = null): void
    {
        if (! $departmentId || ! ($budget = $this->current($organizationId, $departmentId, $on))) {
            return;
        }
        $after = bcadd($this->committed($budget, $exceptOrderId), $newTotal, 2);
        if (bccomp($after, (string) $budget->amount, 2) > 0) {
            throw ValidationException::withMessages(['budget' => 'This order would take the department to '.$after.' against a budget of '.$budget->amount.'.']);
        }
    }
}
