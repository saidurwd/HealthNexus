<?php

namespace App\Models\Billing;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingPriceListItem extends Model
{
    use HasFactory;

    protected $fillable = ['price_list_id', 'billing_item_id', 'department_id', 'provider_id', 'corporate_id', 'insurance_policy_id', 'patient_category', 'scope_hash', 'unit_price', 'minimum_price', 'maximum_price', 'discount_type', 'discount_value', 'tax_included', 'priority', 'effective_from', 'effective_to', 'is_active'];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2', 'minimum_price' => 'decimal:2', 'maximum_price' => 'decimal:2', 'discount_value' => 'decimal:2', 'priority' => 'integer', 'tax_included' => 'boolean', 'is_active' => 'boolean', 'effective_from' => 'date', 'effective_to' => 'date'];
    }

    public function priceList(): BelongsTo { return $this->belongsTo(BillingPriceList::class, 'price_list_id'); }
    public function billingItem(): BelongsTo { return $this->belongsTo(BillingItem::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function provider(): BelongsTo { return $this->belongsTo(User::class, 'provider_id'); }
    public function corporate(): BelongsTo { return $this->belongsTo(BillingCorporate::class); }
    public function insurancePolicy(): BelongsTo { return $this->belongsTo(BillingInsurancePolicy::class); }

    /**
     * This table carries no company_id/branch_id columns of its own — tenant scope is inherited
     * from the parent price list, so this filters via that relation instead of a literal column.
     */
    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->whereHas('priceList', fn ($q) => $q->forTenant($companyId, $branchId));
    }
}
