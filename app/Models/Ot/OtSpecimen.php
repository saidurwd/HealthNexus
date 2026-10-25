<?php

namespace App\Models\Ot;

use App\Models\ClinicalOrder;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtSpecimen extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'surgery_id', 'patient_id', 'encounter_id', 'specimen_number',
        'specimen_type', 'description', 'site', 'laterality', 'collected_at', 'collected_by',
        'container', 'label', 'destination', 'clinical_order_id',
    ];

    protected function casts(): array
    {
        return ['collected_at' => 'datetime'];
    }

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(OtSurgery::class, 'surgery_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function clinicalOrder(): BelongsTo
    {
        return $this->belongsTo(ClinicalOrder::class);
    }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
