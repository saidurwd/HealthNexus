<?php

namespace App\Models\Pharmacy;

use App\Models\Department;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacyOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'branch_id', 'department_id', 'prescription_id', 'patient_id',
        'encounter_id', 'order_number', 'status', 'ordered_by', 'ordered_at',
        'cancelled_by', 'cancelled_at', 'cancellation_reason',
    ];

    protected function casts(): array
    {
        return ['ordered_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function prescription(): BelongsTo { return $this->belongsTo(Prescription::class); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function encounter(): BelongsTo { return $this->belongsTo(Encounter::class); }
    public function orderedBy(): BelongsTo { return $this->belongsTo(User::class, 'ordered_by'); }
    public function items(): HasMany { return $this->hasMany(PharmacyOrderItem::class, 'order_id'); }
    public function dispensings(): HasMany { return $this->hasMany(PharmacyDispensing::class, 'order_id'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
