<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DoctorSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'doctor_id',
        'provider_id',
        'department_id',
        'specialty_id',
        'room_id',
        'name',
        'description',
        'day_of_week',
        'start_time',
        'end_time',
        'effective_from',
        'effective_to',
        'slot_duration_minutes',
        'buffer_minutes',
        'break_start_time',
        'break_end_time',
        'default_capacity_per_slot',
        'overbooking_limit',
        'appointment_type_ids',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'break_start_time' => 'datetime:H:i',
        'break_end_time' => 'datetime:H:i',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'appointment_type_ids' => 'array',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(AppointmentRoom::class, 'room_id');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(AppointmentSlot::class, 'schedule_id');
    }

    /**
     * Whether $date falls within this session's own effective_from/effective_to window (either
     * bound is optional; an unset bound means "no restriction on that side").
     */
    public function isEffectiveOn(\Carbon\Carbon $date): bool
    {
        if ($this->effective_from && $date->lt($this->effective_from)) {
            return false;
        }

        if ($this->effective_to && $date->gt($this->effective_to)) {
            return false;
        }

        return true;
    }
}
