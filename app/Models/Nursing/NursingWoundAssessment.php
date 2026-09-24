<?php

namespace App\Models\Nursing;

use App\Models\Ipd\IpdAdmission;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class NursingWoundAssessment extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'episode_id', 'admission_id', 'patient_id',
        'location', 'wound_type', 'size', 'appearance', 'drainage', 'dressing',
        'intervention', 'reassessment_due_at', 'assessed_by', 'assessed_at',
    ];

    protected function casts(): array
    {
        return ['reassessment_due_at' => 'datetime', 'assessed_at' => 'datetime'];
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

    /**
     * Images attached via the generic File/FileService (decision #11) — entity_type/entity_id
     * polymorphic columns, never a raw file_path string column on this table.
     */
    public function files(): MorphMany
    {
        return $this->morphMany(\App\Models\File::class, 'entity');
    }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
