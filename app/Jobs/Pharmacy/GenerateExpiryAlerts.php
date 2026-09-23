<?php

namespace App\Jobs\Pharmacy;

use App\Models\Pharmacy\PharmacyBatch;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Scans non-quarantined batches with remaining stock against the configurable near-expiry
 * threshold (spec §18) and notifies pharmacy roles — alert only, never auto-adjusts or
 * auto-reorders (Phase 15 owns procurement).
 */
class GenerateExpiryAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SettingsService $settings, NotificationService $notifications): void
    {
        $thresholdDays = (int) $settings->get('pharmacy.near_expiry_threshold_days', 90);
        $cutoff = now()->addDays($thresholdDays)->toDateString();

        $batches = PharmacyBatch::query()
            ->where('is_quarantined', false)
            ->where('expiry_date', '<=', $cutoff)
            ->where('expiry_date', '>', now()->toDateString())
            ->whereHas('stock', fn ($q) => $q->where('quantity_available', '>', 0))
            ->with(['medication', 'stock.store'])
            ->get();

        foreach ($batches as $batch) {
            $recipients = User::role(['pharmacist', 'senior_pharmacist', 'pharmacy_manager', 'pharmacy_administrator'])
                ->whereHas('companies', fn ($q) => $q->where('companies.id', $batch->company_id))
                ->get();

            if ($recipients->isEmpty()) {
                continue;
            }

            foreach ($batch->stock->where('quantity_available', '>', 0) as $stockRow) {
                $notifications->send($recipients, 'pharmacy_expiry_alert', [
                    'batch_number' => $batch->batch_number,
                    'medication' => $batch->medication->name,
                    'store' => $stockRow->store->name,
                    'expiry_date' => $batch->expiry_date->toDateString(),
                ]);
            }
        }
    }
}
