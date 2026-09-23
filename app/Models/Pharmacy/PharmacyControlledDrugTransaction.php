<?php

namespace App\Models\Pharmacy;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyControlledDrugTransaction extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'store_id', 'medication_id', 'batch_id', 'type', 'direction',
        'quantity', 'balance_after', 'reference_type', 'reference_id', 'performed_by',
        'witnessed_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function store(): BelongsTo { return $this->belongsTo(PharmacyStore::class, 'store_id'); }
    public function medication(): BelongsTo { return $this->belongsTo(PharmacyMedication::class, 'medication_id'); }
    public function batch(): BelongsTo { return $this->belongsTo(PharmacyBatch::class, 'batch_id'); }
    public function performedBy(): BelongsTo { return $this->belongsTo(User::class, 'performed_by'); }
    public function witnessedBy(): BelongsTo { return $this->belongsTo(User::class, 'witnessed_by'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
