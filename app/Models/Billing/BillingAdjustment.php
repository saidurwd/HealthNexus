<?php

namespace App\Models\Billing;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BillingAdjustment extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'branch_id', 'invoice_id', 'payment_id', 'adjustable_type', 'adjustable_id', 'type', 'currency', 'original_value', 'new_value', 'difference', 'reason', 'status', 'requested_by', 'requested_at', 'approved_by', 'approved_at', 'approval_note'];

    protected function casts(): array
    {
        return ['original_value' => 'decimal:2', 'new_value' => 'decimal:2', 'difference' => 'decimal:2', 'requested_at' => 'datetime', 'approved_at' => 'datetime'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(BillingInvoice::class); }
    public function payment(): BelongsTo { return $this->belongsTo(BillingPayment::class); }
    public function adjustable(): MorphTo { return $this->morphTo(); }
    public function requestedBy(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }

    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isPending(): bool { return $this->status === 'requested'; }
}
