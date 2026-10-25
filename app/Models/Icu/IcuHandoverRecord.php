<?php

namespace App\Models\Icu;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcuHandoverRecord extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_ACKNOWLEDGEMENT = 'pending_acknowledgement';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const TYPE_NURSE = 'nurse';

    public const TYPE_PHYSICIAN = 'physician';

    public const TYPE_SHIFT = 'shift';

    public const TYPE_TRANSFER = 'transfer';

    protected $fillable = [
        'organization_id', 'branch_id', 'episode_id', 'outgoing_user_id', 'incoming_user_id',
        'handover_type', 'sections', 'status', 'prepared_at', 'acknowledged_at',
    ];

    protected function casts(): array
    {
        return ['sections' => 'array', 'prepared_at' => 'datetime', 'acknowledged_at' => 'datetime'];
    }

    public function episode(): BelongsTo { return $this->belongsTo(IcuEpisode::class, 'episode_id'); }
    public function outgoingUser(): BelongsTo { return $this->belongsTo(User::class, 'outgoing_user_id'); }
    public function incomingUser(): BelongsTo { return $this->belongsTo(User::class, 'incoming_user_id'); }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
