<?php

namespace App\Models\Vital;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Database\Factories\Vital\VrMedicolegalCaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VrMedicolegalCase extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): VrMedicolegalCaseFactory
    {
        return VrMedicolegalCaseFactory::new();
    }

    protected $table = 'vr_medicolegal_cases';

    protected $guarded = ['id'];

    protected $casts = ['occurred_at' => 'datetime', 'brought_at' => 'datetime', 'police_informed_at' => 'datetime', 'postmortem_requested_at' => 'datetime', 'postmortem_done_at' => 'datetime', 'closed_at' => 'datetime'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
