<?php

namespace App\Models\Nursing;

use App\Models\Encounter;
use App\Models\Ipd\IpdAdmission;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The generic configurable-observation framework Phase 3 never built (decision #1 of the Phase 9
 * plan) — scoped narrowly to observation types VitalSign's fixed schema can't hold (pain,
 * consciousness, blood glucose, custom). Never updated in place: a correction inserts a new row
 * with status=corrected referencing the row it corrects.
 */
class NursingObservation extends Model
{
    public const STATUS_PRELIMINARY = 'preliminary';

    public const STATUS_FINAL = 'final';

    public const STATUS_CORRECTED = 'corrected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id', 'branch_id', 'episode_id', 'admission_id', 'patient_id', 'encounter_id',
        'observation_type', 'value', 'unit', 'reference_range_low', 'reference_range_high',
        'status', 'observed_at', 'observed_by', 'device_source', 'corrects_observation_id',
    ];

    protected function casts(): array
    {
        return ['observed_at' => 'datetime', 'reference_range_low' => 'float', 'reference_range_high' => 'float'];
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

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function observedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'observed_by');
    }

    public function corrects(): BelongsTo
    {
        return $this->belongsTo(self::class, 'corrects_observation_id');
    }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
