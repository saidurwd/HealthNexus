<?php

namespace App\Models\Mch;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Database\Factories\Mch\MchPregnancyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MchPregnancy extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): MchPregnancyFactory
    {
        return MchPregnancyFactory::new();
    }

    protected $table = 'mch_pregnancies';

    protected $guarded = ['id'];

    protected $casts = ['lmp' => 'date:Y-m-d', 'edd' => 'date:Y-m-d', 'risk_factors' => 'array', 'booked_at' => 'datetime', 'closed_at' => 'datetime'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /** @return HasMany<MchAncVisit, $this> */
    public function visits(): HasMany
    {
        return $this->hasMany(MchAncVisit::class, 'pregnancy_id');
    }

    /** @return HasOne<MchDelivery, $this> */
    public function delivery(): HasOne
    {
        return $this->hasOne(MchDelivery::class, 'pregnancy_id');
    }
}
