<?php

namespace App\Models\Quality;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Database\Factories\Quality\QaInfectionCaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaInfectionCase extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): QaInfectionCaseFactory
    {
        return QaInfectionCaseFactory::new();
    }

    protected $table = 'qa_infection_cases';

    protected $guarded = ['id'];

    protected $casts = ['onset_on' => 'date:Y-m-d', 'decided_at' => 'datetime'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
