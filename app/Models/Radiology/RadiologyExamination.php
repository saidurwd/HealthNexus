<?php

namespace App\Models\Radiology;

use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RadiologyExamination extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'order_item_id', 'modality_id', 'patient_id', 'encounter_id',
        'appointment_id', 'scheduled_at', 'check_in_at', 'started_at', 'completed_at', 'status',
        'technologist_id', 'performing_provider_id', 'assigned_radiologist_id', 'assigned_at', 'assigned_by',
        'clinical_notes', 'technical_notes', 'mri_safety_screening', 'mri_screened_by', 'mri_screened_at',
        'contrast_agent_id', 'contrast_route', 'contrast_dose', 'contrast_administered_at',
        'contrast_administered_by', 'contrast_reaction', 'contrast_reaction_severity', 'contrast_action_taken',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'check_in_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'assigned_at' => 'datetime',
            'mri_safety_screening' => 'array',
            'mri_screened_at' => 'datetime',
            'contrast_administered_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function orderItem(): BelongsTo { return $this->belongsTo(RadiologyOrderItem::class, 'order_item_id'); }
    public function modality(): BelongsTo { return $this->belongsTo(RadiologyModality::class, 'modality_id'); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function encounter(): BelongsTo { return $this->belongsTo(Encounter::class); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function technologist(): BelongsTo { return $this->belongsTo(Provider::class, 'technologist_id'); }
    public function performingProvider(): BelongsTo { return $this->belongsTo(Provider::class, 'performing_provider_id'); }
    public function assignedRadiologist(): BelongsTo { return $this->belongsTo(Provider::class, 'assigned_radiologist_id'); }
    public function assignedBy(): BelongsTo { return $this->belongsTo(User::class, 'assigned_by'); }
    public function mriScreenedBy(): BelongsTo { return $this->belongsTo(User::class, 'mri_screened_by'); }
    public function contrastAgent(): BelongsTo { return $this->belongsTo(RadiologyContrastAgent::class, 'contrast_agent_id'); }
    public function contrastAdministeredBy(): BelongsTo { return $this->belongsTo(User::class, 'contrast_administered_by'); }
    public function studies(): HasMany { return $this->hasMany(RadiologyStudy::class, 'radiology_examination_id'); }
    public function report(): HasOne { return $this->hasOne(RadiologyReport::class, 'examination_id')->where('is_current', true); }
    public function reports(): HasMany { return $this->hasMany(RadiologyReport::class, 'examination_id'); }

    public function isAssigned(): bool { return $this->assigned_radiologist_id !== null; }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
