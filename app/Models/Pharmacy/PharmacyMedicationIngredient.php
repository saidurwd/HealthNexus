<?php

namespace App\Models\Pharmacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyMedicationIngredient extends Model
{
    protected $fillable = ['medication_id', 'generic_id', 'strength', 'unit'];

    public function medication(): BelongsTo { return $this->belongsTo(PharmacyMedication::class, 'medication_id'); }
    public function generic(): BelongsTo { return $this->belongsTo(PharmacyGeneric::class, 'generic_id'); }
}
