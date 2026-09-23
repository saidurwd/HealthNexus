<?php

namespace App\Models\Pharmacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacyDispensingItem extends Model
{
    protected $fillable = [
        'dispensing_id', 'order_item_id', 'medication_id', 'batch_id',
        'quantity_prescribed', 'quantity_dispensed', 'unit',
        'substitution_flag', 'substitution_reason', 'substituted_from_medication_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity_prescribed' => 'integer',
            'quantity_dispensed' => 'integer',
            'substitution_flag' => 'boolean',
        ];
    }

    public function dispensing(): BelongsTo { return $this->belongsTo(PharmacyDispensing::class, 'dispensing_id'); }
    public function orderItem(): BelongsTo { return $this->belongsTo(PharmacyOrderItem::class, 'order_item_id'); }
    public function medication(): BelongsTo { return $this->belongsTo(PharmacyMedication::class, 'medication_id'); }
    public function batch(): BelongsTo { return $this->belongsTo(PharmacyBatch::class, 'batch_id'); }
    public function substitutedFromMedication(): BelongsTo { return $this->belongsTo(PharmacyMedication::class, 'substituted_from_medication_id'); }
    public function safetyAlerts(): HasMany { return $this->hasMany(PharmacySafetyAlert::class, 'dispensing_item_id'); }
}
