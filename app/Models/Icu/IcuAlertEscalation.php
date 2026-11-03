<?php

namespace App\Models\Icu;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcuAlertEscalation extends Model
{
    protected $fillable = [
        'alert_id', 'escalated_to', 'escalated_to_user_id', 'escalated_by', 'escalated_at',
        'action_taken', 'resolved_at', 'resolved_by',
    ];

    protected function casts(): array
    {
        return ['escalated_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(IcuCriticalAlert::class, 'alert_id');
    }

    public function escalatedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to_user_id');
    }

    public function escalatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
