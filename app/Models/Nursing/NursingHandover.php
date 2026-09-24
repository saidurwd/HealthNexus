<?php

namespace App\Models\Nursing;

use App\Models\Ipd\IpdAdmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NursingHandover extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_ACKNOWLEDGEMENT = 'pending_acknowledgement';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    protected $fillable = [
        'company_id', 'branch_id', 'episode_id', 'admission_id', 'outgoing_nurse_id',
        'incoming_nurse_id', 'shift_id', 'status', 'prepared_at', 'acknowledged_at',
    ];

    protected function casts(): array
    {
        return ['prepared_at' => 'datetime', 'acknowledged_at' => 'datetime'];
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(NursingEpisode::class, 'episode_id');
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IpdAdmission::class, 'admission_id');
    }

    public function outgoingNurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'outgoing_nurse_id');
    }

    public function incomingNurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'incoming_nurse_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(NursingShift::class, 'shift_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(NursingHandoverItem::class, 'handover_id');
    }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
