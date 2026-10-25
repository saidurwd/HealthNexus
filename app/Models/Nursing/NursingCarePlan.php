<?php

namespace App\Models\Nursing;

use App\Models\Ipd\IpdAdmission;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NursingCarePlan extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_DISCONTINUED = 'discontinued';

    public const STATUS_SUPERSEDED = 'superseded';

    protected $fillable = ['organization_id', 'branch_id', 'episode_id', 'admission_id', 'patient_id', 'status', 'created_by'];

    public function episode(): BelongsTo
    {
        return $this->belongsTo(NursingEpisode::class, 'episode_id');
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IpdAdmission::class, 'admission_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(NursingDiagnosis::class, 'care_plan_id');
    }

    public function goals(): HasMany
    {
        return $this->hasMany(NursingCarePlanGoal::class, 'care_plan_id');
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(NursingCarePlanIntervention::class, 'care_plan_id');
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
