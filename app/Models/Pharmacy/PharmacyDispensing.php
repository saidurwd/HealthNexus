<?php

namespace App\Models\Pharmacy;

use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacyDispensing extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PARTIALLY_DISPENSED = 'partially_dispensed';
    public const STATUS_FULLY_DISPENSED = 'fully_dispensed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_RETURNED = 'returned';

    protected $fillable = [
        'company_id', 'branch_id', 'order_id', 'prescription_id', 'patient_id', 'store_id',
        'dispensing_number', 'dispensed_by', 'dispensed_at', 'verified_by', 'verified_at',
        'status', 'remarks',
    ];

    protected function casts(): array
    {
        return ['dispensed_at' => 'datetime', 'verified_at' => 'datetime'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function order(): BelongsTo { return $this->belongsTo(PharmacyOrder::class, 'order_id'); }
    public function prescription(): BelongsTo { return $this->belongsTo(Prescription::class); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function store(): BelongsTo { return $this->belongsTo(PharmacyStore::class, 'store_id'); }
    public function dispensedBy(): BelongsTo { return $this->belongsTo(User::class, 'dispensed_by'); }
    public function verifiedBy(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
    public function items(): HasMany { return $this->hasMany(PharmacyDispensingItem::class, 'dispensing_id'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
