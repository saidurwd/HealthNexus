<?php

namespace App\Models\Welfare;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Database\Factories\Welfare\WfAssessmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WfAssessment extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): WfAssessmentFactory
    {
        return WfAssessmentFactory::new();
    }

    protected $table = 'wf_assessments';

    protected $guarded = ['id'];

    protected $casts = ['factors' => 'array', 'monthly_income' => 'decimal:2', 'per_head' => 'decimal:2', 'valid_until' => 'date', 'decided_at' => 'datetime'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /** @return HasMany<WfGrant, $this> */
    public function grants(): HasMany
    {
        return $this->hasMany(WfGrant::class, 'assessment_id');
    }
}
