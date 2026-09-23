<?php

namespace App\Models\Pharmacy;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacyStockCount extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'branch_id', 'store_id', 'count_number', 'status',
        'started_by', 'started_at', 'completed_by', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function store(): BelongsTo { return $this->belongsTo(PharmacyStore::class, 'store_id'); }
    public function startedBy(): BelongsTo { return $this->belongsTo(User::class, 'started_by'); }
    public function completedBy(): BelongsTo { return $this->belongsTo(User::class, 'completed_by'); }
    public function items(): HasMany { return $this->hasMany(PharmacyStockCountItem::class, 'stock_count_id'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
