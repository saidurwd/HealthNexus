<?php

namespace App\Models\Pharmacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyStock extends Model
{
    protected $table = 'pharmacy_stock';

    protected $fillable = ['store_id', 'medication_id', 'batch_id', 'quantity_available'];

    protected function casts(): array
    {
        return ['quantity_available' => 'integer'];
    }

    public function store(): BelongsTo { return $this->belongsTo(PharmacyStore::class, 'store_id'); }
    public function medication(): BelongsTo { return $this->belongsTo(PharmacyMedication::class, 'medication_id'); }
    public function batch(): BelongsTo { return $this->belongsTo(PharmacyBatch::class, 'batch_id'); }
}
