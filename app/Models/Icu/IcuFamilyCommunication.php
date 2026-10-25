<?php

namespace App\Models\Icu;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcuFamilyCommunication extends Model
{
    protected $fillable = [
        'organization_id', 'branch_id', 'episode_id', 'communicated_at', 'family_contact', 'relationship',
        'communication_type', 'participants', 'clinical_information_shared', 'treatment_discussion',
        'prognosis_discussion', 'questions', 'decisions', 'follow_up', 'documented_by',
    ];

    protected function casts(): array
    {
        return ['communicated_at' => 'datetime'];
    }

    public function episode(): BelongsTo { return $this->belongsTo(IcuEpisode::class, 'episode_id'); }
    public function documentedBy(): BelongsTo { return $this->belongsTo(User::class, 'documented_by'); }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
