<?php

namespace App\Models\Facility;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacVisitorPass extends Model
{
    use HasPublicId;

    protected $table = 'fac_visitor_passes';

    protected $guarded = ['id'];

    protected $casts = ['valid_from' => 'datetime', 'valid_until' => 'datetime', 'id_number' => 'encrypted'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
