<?php

namespace App\Models\Facility;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacAmbulanceTrip extends Model
{
    use HasPublicId;

    protected $table = 'fac_ambulance_trips';

    protected $guarded = ['id'];

    protected $casts = ['requested_at' => 'datetime', 'dispatched_at' => 'datetime', 'arrived_at' => 'datetime', 'completed_at' => 'datetime'];

    /** @return BelongsTo<FacAmbulance, $this> */
    public function ambulance(): BelongsTo
    {
        return $this->belongsTo(FacAmbulance::class, 'ambulance_id');
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
