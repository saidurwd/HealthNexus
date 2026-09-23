<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentReminder extends Model
{
    protected $fillable = [
        'company_id',
        'appointment_id',
        'rule_id',
        'channel',
        'scheduled_for',
        'sent_at',
        'status',
        'failure_reason',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AppointmentReminderRule::class, 'rule_id');
    }

    public function isDue(): bool
    {
        return $this->status === 'pending' && $this->scheduled_for->isPast();
    }
}
