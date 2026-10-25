<?php

namespace App\Models\Ot;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtSurgeryScheduleEvent extends Model
{
    public const TYPE_SCHEDULED = 'scheduled';

    public const TYPE_RESCHEDULED = 'rescheduled';

    public const TYPE_POSTPONED = 'postponed';

    public const TYPE_CANCELLED = 'cancelled';

    public const TYPE_DELAYED = 'delayed';

    protected $fillable = [
        'surgery_id', 'theatre_id', 'change_type', 'scheduled_date', 'scheduled_start_at',
        'scheduled_end_at', 'reason', 'delay_category', 'delay_minutes', 'changed_by', 'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'scheduled_start_at' => 'datetime',
            'scheduled_end_at' => 'datetime',
            'changed_at' => 'datetime',
        ];
    }

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(OtSurgery::class, 'surgery_id');
    }

    public function theatre(): BelongsTo
    {
        return $this->belongsTo(OtTheatre::class, 'theatre_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
