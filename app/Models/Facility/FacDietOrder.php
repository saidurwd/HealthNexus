<?php

namespace App\Models\Facility;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacDietOrder extends Model
{
    use HasPublicId;

    protected $table = 'fac_diet_orders';

    protected $guarded = ['id'];

    protected $casts = ['starts_on' => 'date:Y-m-d', 'ends_on' => 'date:Y-m-d'];

    /** @return BelongsTo<FacDietType, $this> */
    public function diet(): BelongsTo
    {
        return $this->belongsTo(FacDietType::class, 'diet_type_id');
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
