<?php

namespace App\Services\Import\Definitions;

use App\Models\Pharmacy\PharmacyBatch;
use App\Models\Pharmacy\PharmacyMedication;
use App\Models\Pharmacy\PharmacyStock;
use App\Models\Pharmacy\PharmacyStockTransaction;
use App\Models\Pharmacy\PharmacyStore;
use App\Services\Import\ImportContext;
use App\Services\Pharmacy\PharmacyStockService;

/** Opening pharmacy stock. Goes through PharmacyStockService, so every row is also a stock-ledger entry (H-06). */
class OpeningStockImport extends BaseImport
{
    public function key(): string
    {
        return 'opening_stock';
    }

    public function title(): string
    {
        return 'Opening stock (pharmacy)';
    }

    public function permission(): string
    {
        return 'import.opening_stock';
    }

    public function columns(): array
    {
        return [
            ['name' => 'store_code', 'required' => true, 'example' => 'PH-MAIN', 'help' => 'The pharmacy store must already exist'],
            ['name' => 'medication_code', 'required' => true, 'example' => 'NAPA-500', 'help' => 'The drug must already exist'],
            ['name' => 'batch_number', 'required' => true, 'example' => 'B2026-001'],
            ['name' => 'expiry_date', 'required' => true, 'example' => '2027-12-31'],
            ['name' => 'quantity', 'required' => true, 'example' => '500'],
            ['name' => 'unit_cost', 'required' => false, 'example' => '1.20'],
            ['name' => 'selling_price', 'required' => false, 'example' => '1.50'],
        ];
    }

    public function validate(array $raw, ImportContext $context): array
    {
        $errors = [];
        $storeCode = $this->required($raw, 'store_code', $errors, 50);
        $medicationCode = $this->required($raw, 'medication_code', $errors, 50);

        $store = $storeCode ? PharmacyStore::query()->where('organization_id', $context->organizationId)->where('code', $storeCode)->first() : null;
        $storeCode && ! $store && $errors[] = "store_code [$storeCode] does not exist.";
        $medication = $medicationCode ? PharmacyMedication::query()->where('organization_id', $context->organizationId)->where('code', $medicationCode)->first() : null;
        $medicationCode && ! $medication && $errors[] = "medication_code [$medicationCode] does not exist. Import the drugs first.";

        $expiry = $this->date($raw, 'expiry_date', $errors, true);
        $expiry && $expiry <= now()->toDateString() && $errors[] = 'expiry_date is in the past; expired stock cannot be received.';
        $quantity = $this->number($raw, 'quantity', $errors, true, 1);
        $quantity !== null && floor((float) $quantity) != (float) $quantity && $errors[] = 'quantity must be a whole number.';

        return ['errors' => $errors, 'data' => [
            'store_id' => $store?->id, 'medication_id' => $medication?->id,
            'batch_number' => $this->required($raw, 'batch_number', $errors, 100), 'expiry_date' => $expiry,
            'quantity' => $quantity !== null ? (int) $quantity : null,
            'unit_cost' => $this->number($raw, 'unit_cost', $errors, false, 0), 'selling_price' => $this->number($raw, 'selling_price', $errors, false, 0),
        ]];
    }

    public function fileKey(array $data): ?string
    {
        return $data['medication_id'].'|'.strtoupper($data['batch_number']);
    }

    public function findExisting(array $data, ImportContext $context): ?string
    {
        $hit = PharmacyBatch::query()->where('organization_id', $context->organizationId)->where('medication_id', $data['medication_id'])->where('batch_number', $data['batch_number'])->first();

        return $hit ? "batch {$hit->batch_number} of this drug" : null;
    }

    public function commit(array $data, ImportContext $context): array
    {
        $batch = app(PharmacyStockService::class)->receiveNewBatch(
            PharmacyStore::findOrFail($data['store_id']), PharmacyMedication::findOrFail($data['medication_id']),
            ['batch_number' => $data['batch_number'], 'expiry_date' => $data['expiry_date'], 'unit_cost' => $data['unit_cost'], 'selling_price' => $data['selling_price']],
            $data['quantity'], $context->user, PharmacyStockTransaction::TYPE_OPENING,
        );

        return ['type' => PharmacyBatch::class, 'id' => $batch->id];
    }

    /** Puts the received quantity back out through the stock service (a ledger row), only if none of it has been used since. */
    public function rollback(string $type, int $id, ImportContext $context): string
    {
        $batch = PharmacyBatch::query()->where('organization_id', $context->organizationId)->find($id);
        if (! $batch) {
            return 'removed';
        }

        $opening = PharmacyStockTransaction::query()->where('batch_id', $id)->where('type', PharmacyStockTransaction::TYPE_OPENING)->first();
        $stock = PharmacyStock::query()->where('batch_id', $id)->first();
        $movements = PharmacyStockTransaction::query()->where('batch_id', $id)->count();

        if (! $opening || ! $stock || $movements > 1 || $stock->quantity_available < $opening->quantity) {
            return 'kept'; // stock has moved since; reverse it by hand with a stock adjustment
        }

        app(PharmacyStockService::class)->debit(
            PharmacyStore::findOrFail($stock->store_id), PharmacyMedication::findOrFail($batch->medication_id), $batch,
            $opening->quantity, PharmacyStockTransaction::TYPE_CORRECTION, $context->user, 'Import rolled back',
        );

        return 'deactivated'; // the batch record stays (history); its stock is back to zero
    }
}
