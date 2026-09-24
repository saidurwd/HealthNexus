<?php

namespace App\Models\Nursing;

use App\Models\Ipd\IpdAdmission;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NursingRiskAssessment extends Model
{
    public const TYPE_FALL = 'fall';

    public const TYPE_PRESSURE_INJURY = 'pressure_injury';

    public const TYPE_NUTRITION = 'nutrition';

    public const TYPE_INFECTION = 'infection';

    public const TYPE_ASPIRATION = 'aspiration';

    protected $fillable = [
        'company_id', 'branch_id', 'episode_id', 'admission_id', 'patient_id',
        'risk_type', 'tool_name', 'score', 'risk_level', 'contributing_factors', 'interventions',
        'assessed_by', 'assessed_at', 'reassessment_due_at',
    ];

    protected function casts(): array
    {
        return ['assessed_at' => 'datetime', 'reassessment_due_at' => 'datetime'];
    }

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

    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
