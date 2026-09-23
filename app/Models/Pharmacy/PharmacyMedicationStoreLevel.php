<?php

namespace App\Models\Pharmacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyMedicationStoreLevel extends Model
{
    protected $fillable = ['medication_id', 'store_id', 'minimum_stock', 'reorder_level', 'maximum_stock'];

    protected function casts(): array
    {
        return [
            'minimum_stock' => 'integer',
            'reorder_level' => 'integer',
            'maximum_stock' => 'integer',
        ];
    }

    public function medication(): BelongsTo { return $this->belongsTo(PharmacyMedication::class, 'medication_id'); }
    public function store(): BelongsTo { return $this->belongsTo(PharmacyStore::class, 'store_id'); }
}
