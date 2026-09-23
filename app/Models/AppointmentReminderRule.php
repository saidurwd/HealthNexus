<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentReminderRule extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'department_id',
        'provider_id',
        'appointment_type_id',
        'channel',
        'offset_minutes',
        'is_active',
    ];

    protected $casts = [
        'offset_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class);
    }

    /**
     * Whether this rule applies to a given appointment — an unset scoping field (null) matches
     * anything, a set one must match exactly.
     */
    public function appliesTo(Appointment $appointment): bool
    {
        return ($this->branch_id === null || $this->branch_id === $appointment->branch_id)
            && ($this->department_id === null || $this->department_id === $appointment->slot?->schedule?->department_id)
            && ($this->provider_id === null || $this->provider_id === $appointment->provider_id)
            && ($this->appointment_type_id === null || $this->appointment_type_id === $appointment->appointment_type_id);
    }
}
