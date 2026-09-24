<?php

namespace App\Models\Nursing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Never confused with, and never touching, the physician `App\Models\Diagnosis` table (spec §23).
 */
class NursingDiagnosis extends Model
{
    protected $fillable = [
        'care_plan_id', 'diagnosis_text', 'related_factors', 'evidence', 'priority', 'status',
        'coding_system', 'code', 'created_by',
    ];

    public function carePlan(): BelongsTo
    {
        return $this->belongsTo(NursingCarePlan::class, 'care_plan_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
