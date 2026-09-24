<?php

namespace App\Models\Nursing;

use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdDischargeRequest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NursingDischargeChecklist extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'company_id', 'branch_id', 'episode_id', 'admission_id', 'patient_id', 'discharge_request_id',
        'education_completed', 'medication_education_completed', 'devices_removed',
        'belongings_confirmed', 'follow_up_instructions_given', 'status', 'completed_by',
        'completed_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'education_completed' => 'boolean',
            'medication_education_completed' => 'boolean',
            'devices_removed' => 'boolean',
            'belongings_confirmed' => 'boolean',
            'follow_up_instructions_given' => 'boolean',
            'completed_at' => 'datetime',
        ];
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

    public function dischargeRequest(): BelongsTo
    {
        return $this->belongsTo(IpdDischargeRequest::class, 'discharge_request_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
