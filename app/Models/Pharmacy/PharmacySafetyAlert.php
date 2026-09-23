<?php

namespace App\Models\Pharmacy;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacySafetyAlert extends Model
{
    use HasFactory;

    public const TYPE_ALLERGY = 'allergy';
    public const TYPE_DRUG_INTERACTION = 'drug_interaction';
    public const TYPE_EXPIRY = 'expiry';
    public const TYPE_QUARANTINE = 'quarantine';

    public const SEVERITY_MILD = 'mild';
    public const SEVERITY_MODERATE = 'moderate';
    public const SEVERITY_SEVERE = 'severe';

    protected $fillable = [
        'company_id', 'branch_id', 'order_item_id', 'dispensing_item_id', 'medication_id',
        'alert_type', 'severity', 'interacting_reference', 'explanation', 'recommended_action',
        'is_overridden', 'override_reason', 'overridden_by', 'overridden_at',
    ];

    protected function casts(): array
    {
        return [
            'is_overridden' => 'boolean',
            'overridden_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function orderItem(): BelongsTo { return $this->belongsTo(PharmacyOrderItem::class, 'order_item_id'); }
    public function dispensingItem(): BelongsTo { return $this->belongsTo(PharmacyDispensingItem::class, 'dispensing_item_id'); }
    public function medication(): BelongsTo { return $this->belongsTo(PharmacyMedication::class, 'medication_id'); }
    public function overriddenBy(): BelongsTo { return $this->belongsTo(User::class, 'overridden_by'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
