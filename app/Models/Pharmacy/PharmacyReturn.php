<?php

namespace App\Models\Pharmacy;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacyReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id', 'branch_id', 'dispensing_id', 'store_id', 'return_number', 'status',
        'reason', 'requested_by', 'requested_at', 'verified_by', 'verified_at',
    ];

    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'verified_at' => 'datetime'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function dispensing(): BelongsTo
    {
        return $this->belongsTo(PharmacyDispensing::class, 'dispensing_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(PharmacyStore::class, 'store_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PharmacyReturnItem::class, 'return_id');
    }

    public function scopeForTenant($query, int $organizationId, ?int $branchId = null)
    {
        return $query->where('organization_id', $organizationId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
