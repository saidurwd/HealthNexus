<?php

namespace App\Models\Nursing;

use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdBed;
use App\Models\Ipd\IpdWard;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NursingAssignment extends Model
{
    public const TYPE_PATIENT = 'patient';

    public const TYPE_BED = 'bed';

    public const TYPE_WARD = 'ward';

    public const TYPE_SHIFT = 'shift';

    public const TYPE_CHARGE_NURSE = 'charge_nurse';

    public const TYPE_TEAM = 'team';

    protected $fillable = [
        'episode_id', 'admission_id', 'patient_id', 'ward_id', 'bed_id', 'nurse_id', 'shift_id',
        'assignment_type', 'started_at', 'ended_at', 'assigned_by',
    ];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(NursingEpisode::class, 'episode_id');
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IpdAdmission::class, 'admission_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(IpdWard::class, 'ward_id');
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(IpdBed::class, 'bed_id');
    }

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(NursingShift::class, 'shift_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
