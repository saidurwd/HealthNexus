<?php

namespace App\Models\Facility;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacMortuaryCase extends Model
{
    use HasPublicId;

    protected $table = 'fac_mortuary_cases';

    protected $guarded = ['id'];

    protected $casts = ['died_at' => 'datetime', 'received_at' => 'datetime', 'released_at' => 'datetime', 'police_case' => 'boolean', 'police_cleared' => 'boolean', 'id_number' => 'encrypted'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /** @return BelongsTo<FacMortuaryUnit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(FacMortuaryUnit::class, 'unit_id');
    }
}
