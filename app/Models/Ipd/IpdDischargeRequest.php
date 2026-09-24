<?php

namespace App\Models\Ipd;

use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpdDischargeRequest extends Model
{
    use HasFactory;

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_CLINICAL_CLEARANCE = 'clinical_clearance';
    public const STATUS_BILLING_CLEARANCE = 'billing_clearance';
    public const STATUS_PHARMACY_CLEARANCE = 'pharmacy_clearance';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'company_id', 'branch_id', 'admission_id', 'patient_id', 'discharge_type', 'planned_date',
        'reason', 'discharge_diagnosis', 'disposition_id', 'instructions', 'follow_up_required',
        'follow_up_provider_id', 'follow_up_date', 'status',
        'clinical_cleared_at', 'clinical_cleared_by', 'billing_cleared_at', 'billing_cleared_by',
        'pharmacy_cleared_at', 'pharmacy_cleared_by', 'requested_by', 'approved_by', 'approved_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'planned_date' => 'date',
            'follow_up_required' => 'boolean',
            'follow_up_date' => 'date',
            'clinical_cleared_at' => 'datetime',
            'billing_cleared_at' => 'datetime',
            'pharmacy_cleared_at' => 'datetime',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function admission(): BelongsTo { return $this->belongsTo(IpdAdmission::class, 'admission_id'); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function disposition(): BelongsTo { return $this->belongsTo(IpdDischargeDisposition::class, 'disposition_id'); }
    public function followUpProvider(): BelongsTo { return $this->belongsTo(Provider::class, 'follow_up_provider_id'); }
    public function requestedBy(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function clinicalClearedBy(): BelongsTo { return $this->belongsTo(User::class, 'clinical_cleared_by'); }
    public function billingClearedBy(): BelongsTo { return $this->belongsTo(User::class, 'billing_cleared_by'); }
    public function pharmacyClearedBy(): BelongsTo { return $this->belongsTo(User::class, 'pharmacy_cleared_by'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
