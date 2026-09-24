<?php

namespace App\Models\Ipd;

use App\Models\Department;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class IpdAdmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'branch_id', 'admission_number', 'patient_id', 'admission_request_id',
        'encounter_id', 'admission_type_id', 'admission_source_id', 'department_id', 'specialty_id',
        'admitting_provider_id', 'attending_provider_id', 'admitted_at', 'expected_discharge_date',
        'actual_discharge_date', 'priority', 'status', 'discharge_disposition_id',
        'created_by', 'approved_by', 'admitted_by', 'discharged_by',
    ];

    protected function casts(): array
    {
        return [
            'admitted_at' => 'datetime',
            'expected_discharge_date' => 'date',
            'actual_discharge_date' => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function admissionRequest(): BelongsTo { return $this->belongsTo(IpdAdmissionRequest::class, 'admission_request_id'); }
    public function encounter(): BelongsTo { return $this->belongsTo(Encounter::class); }
    public function admissionType(): BelongsTo { return $this->belongsTo(IpdAdmissionType::class, 'admission_type_id'); }
    public function admissionSource(): BelongsTo { return $this->belongsTo(IpdAdmissionSource::class, 'admission_source_id'); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function specialty(): BelongsTo { return $this->belongsTo(Specialty::class); }
    public function admittingProvider(): BelongsTo { return $this->belongsTo(Provider::class, 'admitting_provider_id'); }
    public function attendingProvider(): BelongsTo { return $this->belongsTo(Provider::class, 'attending_provider_id'); }
    public function dischargeDisposition(): BelongsTo { return $this->belongsTo(IpdDischargeDisposition::class, 'discharge_disposition_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function admittedBy(): BelongsTo { return $this->belongsTo(User::class, 'admitted_by'); }
    public function dischargedBy(): BelongsTo { return $this->belongsTo(User::class, 'discharged_by'); }

    public function providerAssignments(): HasMany { return $this->hasMany(IpdProviderAssignment::class, 'admission_id'); }
    public function allocations(): HasMany { return $this->hasMany(IpdBedAllocation::class, 'admission_id'); }
    public function movements(): HasMany { return $this->hasMany(IpdBedMovement::class, 'admission_id'); }
    public function leaves(): HasMany { return $this->hasMany(IpdPatientLeave::class, 'admission_id'); }
    public function dischargeRequests(): HasMany { return $this->hasMany(IpdDischargeRequest::class, 'admission_id'); }

    /**
     * The sole source of "where is this patient now" (spec §17) — never a denormalized column.
     */
    public function currentAllocation(): HasOne
    {
        return $this->hasOne(IpdBedAllocation::class, 'admission_id')->whereNull('released_at')->latestOfMany();
    }

    public function currentBed(): HasOneThrough
    {
        return $this->hasOneThrough(
            IpdBed::class,
            IpdBedAllocation::class,
            'admission_id',
            'id',
            'id',
            'bed_id',
        )->whereNull('ipd_bed_allocations.released_at');
    }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
