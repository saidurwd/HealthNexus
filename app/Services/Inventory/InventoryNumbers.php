<?php

namespace App\Services\Inventory;

use App\Models\User;
use App\Services\Numbering\NumberSeriesService;

/** One place for the document number series of Inventory and Procurement. */
class InventoryNumbers
{
    private const SERIES = [
        'requisition' => 'REQ', 'rfq' => 'RFQ', 'po' => 'PO', 'grn' => 'GRN', 'return' => 'SRT', 'transfer' => 'TRF',
        'request' => 'IRQ', 'issue' => 'ISS', 'count' => 'CNT',
    ];

    public function __construct(private readonly NumberSeriesService $numbers) {}

    public function next(string $kind, int $organizationId, ?User $by = null): string
    {
        return $this->numbers->next("inventory.$kind", $organizationId, null, prefix: self::SERIES[$kind], format: '{PREFIX}-{YEAR}-{SEQ:6}', userId: $by?->id);
    }
}
