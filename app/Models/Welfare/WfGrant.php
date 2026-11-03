<?php

namespace App\Models\Welfare;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Database\Factories\Welfare\WfGrantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WfGrant extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): WfGrantFactory
    {
        return WfGrantFactory::new();
    }

    protected $table = 'wf_grants';

    protected $guarded = ['id'];

    protected $casts = ['cap_amount' => 'decimal:2', 'spent_amount' => 'decimal:2', 'valid_from' => 'date', 'valid_to' => 'date', 'approved_at' => 'datetime'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /** @return BelongsTo<WfFund, $this> */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(WfFund::class, 'fund_id');
    }

    /** @return BelongsTo<WfAssessment, $this> */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(WfAssessment::class, 'assessment_id');
    }

    /** @return HasMany<WfApplication, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(WfApplication::class, 'grant_id');
    }
}
