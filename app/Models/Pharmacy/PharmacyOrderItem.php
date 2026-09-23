<?php

namespace App\Models\Pharmacy;

use App\Models\PrescriptionItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacyOrderItem extends Model
{
    public const STATUS_UNMATCHED = 'unmatched';
    public const STATUS_PENDING = 'pending';
    public const STATUS_DISPENSED = 'dispensed';
    public const STATUS_PARTIALLY_DISPENSED = 'partially_dispensed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_id', 'prescription_item_id', 'medication_id', 'requested_medicine_name',
        'quantity_prescribed', 'quantity_dispensed', 'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity_prescribed' => 'integer',
            'quantity_dispensed' => 'integer',
        ];
    }

    public function order(): BelongsTo { return $this->belongsTo(PharmacyOrder::class, 'order_id'); }
    public function prescriptionItem(): BelongsTo { return $this->belongsTo(PrescriptionItem::class); }
    public function medication(): BelongsTo { return $this->belongsTo(PharmacyMedication::class, 'medication_id'); }
    public function dispensingItems(): HasMany { return $this->hasMany(PharmacyDispensingItem::class, 'order_item_id'); }
    public function safetyAlerts(): HasMany { return $this->hasMany(PharmacySafetyAlert::class, 'order_item_id'); }

    public function remainingQuantity(): ?int
    {
        if ($this->quantity_prescribed === null) {
            return null;
        }

        return max(0, $this->quantity_prescribed - $this->quantity_dispensed);
    }
}
