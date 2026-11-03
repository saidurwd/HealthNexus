<?php

namespace App\Models\Billing;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingPriceList extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'branch_id', 'name', 'code', 'currency', 'status', 'priority', 'effective_from', 'effective_to', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['priority' => 'integer', 'effective_from' => 'date', 'effective_to' => 'date'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillingPriceListItem::class, 'price_list_id');
    }

    public function corporateContracts(): HasMany
    {
        return $this->hasMany(BillingCorporateContract::class, 'price_list_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
