<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'patient_id',
        'doctor_id',
        'provider_id',
        'slot_id',
        'appointment_no',
        'appointment_date',
        'appointment_time',
        'actual_datetime',
        'type',
        'appointment_type_id',
        'specialty_id',
        'room_id',
        'source',
        'reason',
        'status',
        'priority',
        'started_at',
        'ended_at',
        'is_walk_in',
        'is_follow_up',
        'is_telemedicine',
        'previous_appointment_id',
        'referral_source',
        'referred_by',
        'referral_organization',
        'referral_reference',
        'booked_at',
        'confirmed_at',
        'confirmed_by',
        'checked_in_at',
        'checked_in_by',
        'completed_at',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'no_show_at',
        'created_by',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'appointment_time' => 'datetime:H:i',
        'actual_datetime' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'booked_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'no_show_at' => 'datetime',
        'is_walk_in' => 'boolean',
        'is_follow_up' => 'boolean',
        'is_telemedicine' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(AppointmentRoom::class, 'room_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AppointmentDocument::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(AppointmentReminder::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(AppointmentSlot::class, 'slot_id');
    }

    public function token(): HasOne
    {
        return $this->hasOne(AppointmentToken::class);
    }

    public function vitalSigns(): HasMany
    {
        return $this->hasMany(VitalSign::class);
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function investigationOrders(): HasMany
    {
        return $this->hasMany(InvestigationOrder::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function previousAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'previous_appointment_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(AppointmentStatusHistory::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(AppointmentNote::class);
    }
}
