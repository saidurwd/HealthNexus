<?php

namespace App\Services\Pharmacy;

use App\Models\Pharmacy\PharmacyBatch;
use App\Models\Pharmacy\PharmacyDispensingItem;
use App\Models\Pharmacy\PharmacyRecall;
use App\Models\Pharmacy\PharmacyStock;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Quarantines all remaining stock of the recalled batch across every store, then reuses
 * pharmacy_dispensing_items (joined via batch_id) to find affected dispensings/patients directly
 * — no new junction table is needed just to answer "who received this batch."
 */
class PharmacyRecallService
{
    public function __construct(private readonly PharmacyNumberGenerator $numbers, private readonly PharmacyQuarantineService $quarantine) {}

    public function initiate(PharmacyBatch $batch, string $reason, User $user): PharmacyRecall
    {
        return DB::transaction(function () use ($batch, $reason, $user) {
            $recall = PharmacyRecall::create([
                'company_id' => $batch->company_id,
                'branch_id' => $batch->branch_id,
                'medication_id' => $batch->medication_id,
                'batch_id' => $batch->id,
                'recall_number' => $this->numbers->generate($batch->company_id, $batch->branch_id, 'RCL', 'RCL'),
                'reason' => $reason,
                'status' => PharmacyRecall::STATUS_INITIATED,
                'initiated_by' => $user->id,
                'initiated_at' => now(),
            ]);

            $stockRows = PharmacyStock::query()
                ->where('batch_id', $batch->id)
                ->where('quantity_available', '>', 0)
                ->with('store')
                ->get();

            foreach ($stockRows as $stockRow) {
                $this->quarantine->quarantine(
                    $stockRow->store,
                    $batch,
                    $stockRow->quantity_available,
                    'recall',
                    "Recall {$recall->recall_number}: {$reason}",
                    $user,
                );
            }

            return $recall->refresh();
        });
    }

    public function close(PharmacyRecall $recall, User $user): PharmacyRecall
    {
        if ($recall->status !== PharmacyRecall::STATUS_INITIATED) {
            throw ValidationException::withMessages(['recall' => "This recall is already '{$recall->status}'."]);
        }

        $recall->update(['status' => PharmacyRecall::STATUS_CLOSED, 'closed_by' => $user->id, 'closed_at' => now()]);

        return $recall->refresh();
    }

    /**
     * @return Collection<int, PharmacyDispensingItem>
     */
    public function affectedDispensingItems(PharmacyBatch $batch): Collection
    {
        return PharmacyDispensingItem::query()
            ->where('batch_id', $batch->id)
            ->with(['dispensing.patient', 'medication'])
            ->get();
    }
}
