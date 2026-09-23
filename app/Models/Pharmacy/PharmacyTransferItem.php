<?php

namespace App\Models\Pharmacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyTransferItem extends Model
{
    protected $fillable = [
        'transfer_id', 'medication_id', 'batch_id',
        'quantity_requested', 'quantity_dispatched', 'quantity_received',
    ];

    protected function casts(): array
    {
        return [
            'quantity_requested' => 'integer',
            'quantity_dispatched' => 'integer',
            'quantity_received' => 'integer',
        ];
    }

    public function transfer(): BelongsTo { return $this->belongsTo(PharmacyTransfer::class, 'transfer_id'); }
    public function medication(): BelongsTo { return $this->belongsTo(PharmacyMedication::class, 'medication_id'); }
    public function batch(): BelongsTo { return $this->belongsTo(PharmacyBatch::class, 'batch_id'); }
}
