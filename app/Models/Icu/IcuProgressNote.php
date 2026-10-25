<?php

namespace App\Models\Icu;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IcuProgressNote extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINAL = 'final';

    public const TYPE_PROGRESS = 'progress';

    public const TYPE_ROUNDS = 'rounds';

    protected $fillable = [
        'organization_id', 'branch_id', 'episode_id', 'patient_id', 'note_type',
        'overnight_events', 'subjective', 'objective', 'assessment', 'plan',
        'status', 'created_by', 'signed_at', 'signed_by',
    ];

    protected function casts(): array
    {
        return ['signed_at' => 'datetime'];
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(IcuEpisode::class, 'episode_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(IcuProgressNoteAmendment::class, 'note_id');
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
