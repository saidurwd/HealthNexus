<?php

namespace App\Models\Icu;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcuCriticalEvent extends Model
{
    public const TYPE_CODE_BLUE = 'code_blue';

    public const TYPE_RAPID_DETERIORATION = 'rapid_deterioration';

    public const TYPE_EMERGENCY_INTUBATION = 'emergency_intubation';

    public const TYPE_EMERGENCY_PROCEDURE = 'emergency_procedure';

    public const TYPE_OTHER = 'other';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINAL = 'final';

    protected $fillable = [
        'organization_id', 'branch_id', 'episode_id', 'event_type', 'location', 'event_time',
        'recognition_time', 'response_time', 'initial_condition', 'participants', 'interventions',
        'medications', 'procedures', 'defibrillation', 'airway_management', 'rosc', 'outcome',
        'post_event_plan', 'status', 'reported_by', 'finalized_by', 'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'event_time' => 'datetime', 'recognition_time' => 'datetime', 'response_time' => 'datetime',
            'defibrillation' => 'boolean', 'rosc' => 'boolean', 'finalized_at' => 'datetime',
        ];
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(IcuEpisode::class, 'episode_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
