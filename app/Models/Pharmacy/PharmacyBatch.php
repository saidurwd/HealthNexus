<?php

namespace App\Models\Pharmacy;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacyBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'branch_id', 'medication_id', 'batch_number', 'manufacturer',
        'manufacturing_date', 'expiry_date', 'unit_cost', 'selling_price',
        'supplier_reference', 'is_quarantined', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'manufacturing_date' => 'date',
            'expiry_date' => 'date',
            'unit_cost' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'is_quarantined' => 'boolean',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function medication(): BelongsTo { return $this->belongsTo(PharmacyMedication::class, 'medication_id'); }
    public function stock(): HasMany { return $this->hasMany(PharmacyStock::class, 'batch_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function isExpired(): bool
    {
        return $this->expiry_date->isPast();
    }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('pharmacy_batches.company_id', $companyId)
            ->where(fn ($q) => $q->where('pharmacy_batches.branch_id', $branchId)->orWhereNull('pharmacy_batches.branch_id'));
    }
}
