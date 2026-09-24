<?php

namespace App\Models\Nursing;

use App\Models\Ipd\IpdAdmission;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NursingEscalation extends Model
{
    public const RECIPIENT_CHARGE_NURSE = 'charge_nurse';

    public const RECIPIENT_ATTENDING_PHYSICIAN = 'attending_physician';

    public const RECIPIENT_ON_CALL_PHYSICIAN = 'on_call_physician';

    public const RECIPIENT_RAPID_RESPONSE = 'rapid_response';

    public const RECIPIENT_ICU_TEAM = 'icu_team';

    public const SEVERITY_INFORMATIONAL = 'informational';

    public const SEVERITY_LOW = 'low';

    public const SEVERITY_MODERATE = 'moderate';

    public const SEVERITY_HIGH = 'high';

    public const SEVERITY_CRITICAL = 'critical';

    protected $fillable = [
        'company_id', 'branch_id', 'episode_id', 'admission_id', 'patient_id', 'concern',
        'recipient_type', 'recipient_id', 'severity', 'created_by', 'acknowledged_at',
        'acknowledged_by', 'action_taken', 'resolved_at', 'resolved_by',
    ];

    protected function casts(): array
    {
        return ['acknowledged_at' => 'datetime', 'resolved_at' => 'datetime'];
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
