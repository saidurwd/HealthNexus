<?php

namespace App\Models\Billing;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingCashierSession extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'branch_id', 'counter_id', 'user_id', 'status', 'currency', 'opening_balance', 'expected_collections', 'expected_refunds', 'expected_closing', 'actual_closing', 'variance', 'opened_at', 'closed_at', 'closed_by', 'closing_notes'];

    protected function casts(): array
    {
        return ['opening_balance' => 'decimal:2', 'expected_collections' => 'decimal:2', 'expected_refunds' => 'decimal:2', 'expected_closing' => 'decimal:2', 'actual_closing' => 'decimal:2', 'variance' => 'decimal:2', 'opened_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function counter(): BelongsTo { return $this->belongsTo(\App\Models\Department::class, 'counter_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function payments(): HasMany { return $this->hasMany(BillingPayment::class, 'cashier_session_id'); }
    public function closedBy(): BelongsTo { return $this->belongsTo(User::class, 'closed_by'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }

    public function isOpen(): bool { return $this->status === 'open'; }
    public function isClosed(): bool { return $this->status === 'closed'; }
}
