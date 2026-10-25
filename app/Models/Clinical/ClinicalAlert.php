<?php

namespace App\Models\Clinical;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicalAlert extends Model
{
    use HasPublicId;

    public const STATUS_OPEN = 'open';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_OVERRIDDEN = 'overridden';

    protected $guarded = ['id', 'public_id'];

    protected $casts = [
        'ack_required' => 'boolean',
        'read_back_confirmed' => 'boolean',
        'raised_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'next_escalation_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(ClinicalAlertRecipient::class, 'alert_id');
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(ClinicalAlertEscalation::class, 'alert_id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
