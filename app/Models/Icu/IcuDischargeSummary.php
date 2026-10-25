<?php

namespace App\Models\Icu;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcuDischargeSummary extends Model
{
    use HasPublicId;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINAL = 'final';

    protected $fillable = [
        'organization_id', 'branch_id', 'episode_id', 'admission_reason_summary', 'diagnoses',
        'major_events', 'procedures_summary', 'ventilation_summary', 'medications_summary',
        'complications', 'laboratory_course', 'imaging_summary', 'current_status', 'destination',
        'follow_up_plan', 'status', 'created_by', 'finalized_by', 'finalized_at',
    ];

    protected function casts(): array
    {
        return ['finalized_at' => 'datetime'];
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(IcuEpisode::class, 'episode_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
