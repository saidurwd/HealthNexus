<?php

namespace App\Models\Vital;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Database\Factories\Vital\VrDeathCertificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VrDeathCertification extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): VrDeathCertificationFactory
    {
        return VrDeathCertificationFactory::new();
    }

    protected $table = 'vr_death_certifications';

    protected $guarded = ['id'];

    protected $casts = ['died_at' => 'datetime', 'certified_at' => 'datetime', 'issued_at' => 'datetime', 'cancelled_at' => 'datetime', 'autopsy_done' => 'boolean', 'medico_legal' => 'boolean', 'issued_id_number' => 'encrypted'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /** @return BelongsTo<VrMedicolegalCase, $this> */
    public function medicolegalCase(): BelongsTo
    {
        return $this->belongsTo(VrMedicolegalCase::class, 'medicolegal_case_id');
    }
}
