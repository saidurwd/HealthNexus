<?php

namespace App\Models\Pharmacy;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyStockTransaction extends Model
{
    public const TYPE_OPENING = 'opening';
    public const TYPE_PURCHASE_RECEIPT = 'purchase_receipt';
    public const TYPE_TRANSFER_IN = 'transfer_in';
    public const TYPE_TRANSFER_OUT = 'transfer_out';
    public const TYPE_DISPENSING = 'dispensing';
    public const TYPE_RETURN = 'return';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_DAMAGE = 'damage';
    public const TYPE_EXPIRED = 'expired';
    public const TYPE_WASTAGE = 'wastage';
    public const TYPE_CORRECTION = 'correction';

    protected $fillable = [
        'store_id', 'medication_id', 'batch_id', 'type', 'direction', 'quantity',
        'balance_after', 'reference_type', 'reference_id', 'performed_by', 'reason',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function store(): BelongsTo { return $this->belongsTo(PharmacyStore::class, 'store_id'); }
    public function medication(): BelongsTo { return $this->belongsTo(PharmacyMedication::class, 'medication_id'); }
    public function batch(): BelongsTo { return $this->belongsTo(PharmacyBatch::class, 'batch_id'); }
    public function performedBy(): BelongsTo { return $this->belongsTo(User::class, 'performed_by'); }
    public function reference(): \Illuminate\Database\Eloquent\Relations\MorphTo { return $this->morphTo('reference'); }
}
