<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppointmentSlot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'doctor_id',
        'schedule_id',
        'slot_datetime',
        'duration_minutes',
        'max_capacity',
        'booked_count',
        'status',
    ];

    protected $casts = [
        'slot_datetime' => 'datetime',
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

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(DoctorSchedule::class, 'schedule_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'slot_id');
    }

    /**
     * @param  bool  $withOverbooking  when true, checks against max_capacity + the schedule's
     *                                 overbooking_limit instead of the plain capacity — callers
     *                                 must independently verify the actor holds the
     *                                 appointments.override permission before passing true.
     */
    public function isAvailable(bool $withOverbooking = false): bool
    {
        if ($this->status !== 'available') {
            return false;
        }

        $ceiling = $this->max_capacity + ($withOverbooking ? ($this->schedule?->overbooking_limit ?? 0) : 0);

        return $this->booked_count < $ceiling;
    }

    public function remainingCapacity(bool $withOverbooking = false): int
    {
        $ceiling = $this->max_capacity + ($withOverbooking ? ($this->schedule?->overbooking_limit ?? 0) : 0);

        return max(0, $ceiling - $this->booked_count);
    }
}
