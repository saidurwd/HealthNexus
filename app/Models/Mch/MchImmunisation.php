<?php

namespace App\Models\Mch;

use App\Models\Patient;
use Database\Factories\Mch\MchImmunisationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MchImmunisation extends Model
{
    use HasFactory;

    protected static function newFactory(): MchImmunisationFactory
    {
        return MchImmunisationFactory::new();
    }

    protected $table = 'mch_immunisations';

    protected $guarded = ['id'];

    protected $casts = ['given_at' => 'datetime'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
