<?php

namespace App\Models\Pharmacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyStockCountItem extends Model
{
    protected $fillable = [
        'stock_count_id', 'medication_id', 'batch_id',
        'expected_quantity', 'counted_quantity', 'variance', 'is_adjusted',
    ];

    protected function casts(): array
    {
        return [
            'expected_quantity' => 'integer',
            'counted_quantity' => 'integer',
            'variance' => 'integer',
            'is_adjusted' => 'boolean',
        ];
    }

    public function stockCount(): BelongsTo { return $this->belongsTo(PharmacyStockCount::class, 'stock_count_id'); }
    public function medication(): BelongsTo { return $this->belongsTo(PharmacyMedication::class, 'medication_id'); }
    public function batch(): BelongsTo { return $this->belongsTo(PharmacyBatch::class, 'batch_id'); }
}
