<?php

namespace App\Models\Icu;

use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ICU's own generic time-series observation table (spec §43) — not an extension of
 * NursingObservation, per the per-module duplication convention (Phase 11 plan decision #5).
 * Never updated in place: a correction inserts a new row referencing the one it corrects.
 */
class IcuObservation extends Model
{
    protected $fillable = [
        'organization_id', 'branch_id', 'episode_id', 'patient_id', 'encounter_id',
        'observation_type', 'code', 'value_numeric', 'value_text', 'unit',
        'reference_range_low', 'reference_range_high', 'observed_at', 'recorded_by',
        'device_source', 'corrects_observation_id',
    ];

    protected function casts(): array
    {
        return [
            'value_numeric' => 'float',
            'reference_range_low' => 'float',
            'reference_range_high' => 'float',
            'observed_at' => 'datetime',
        ];
    }

    public function episode(): BelongsTo { return $this->belongsTo(IcuEpisode::class, 'episode_id'); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function encounter(): BelongsTo { return $this->belongsTo(Encounter::class); }
    public function recordedBy(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function corrects(): BelongsTo { return $this->belongsTo(self::class, 'corrects_observation_id'); }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
