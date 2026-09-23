<?php

namespace App\Jobs\Pharmacy;

use App\Models\Pharmacy\PharmacyMedicationStoreLevel;
use App\Models\Pharmacy\PharmacyStock;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Compares pharmacy_stock totals per (store, medication) against
 * pharmacy_medication_store_levels.reorder_level and notifies pharmacy roles — alert only, never
 * auto-creates a purchase order (Phase 15 owns procurement).
 */
class CheckLowStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notifications): void
    {
        $levels = PharmacyMedicationStoreLevel::query()
            ->where('reorder_level', '>', 0)
            ->with(['medication', 'store'])
            ->get();

        foreach ($levels as $level) {
            $available = PharmacyStock::query()
                ->where('store_id', $level->store_id)
                ->where('medication_id', $level->medication_id)
                ->sum('quantity_available');

            if ($available > $level->reorder_level) {
                continue;
            }

            $recipients = User::role(['pharmacist', 'senior_pharmacist', 'pharmacy_manager', 'storekeeper', 'pharmacy_administrator'])
                ->whereHas('companies', fn ($q) => $q->where('companies.id', $level->medication->company_id))
                ->get();

            if ($recipients->isEmpty()) {
                continue;
            }

            $notifications->send($recipients, 'pharmacy_low_stock', [
                'medication' => $level->medication->name,
                'store' => $level->store->name,
                'quantity' => (string) $available,
                'reorder_level' => (string) $level->reorder_level,
            ]);
        }
    }
}
