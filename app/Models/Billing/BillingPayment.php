<?php

namespace App\Models\Billing;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BillingPayment extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'branch_id', 'patient_id', 'invoice_id', 'corporate_id', 'payment_method_id', 'cashier_session_id', 'payment_number', 'currency', 'amount', 'transaction_reference', 'payment_date', 'status', 'completed_at', 'cancelled_at', 'received_by', 'cancelled_by', 'notes'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'payment_date' => 'date', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function patient(): BelongsTo { return $this->belongsTo(\App\Models\Patient::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(BillingInvoice::class); }
    public function corporate(): BelongsTo { return $this->belongsTo(BillingCorporate::class); }
    public function method(): BelongsTo { return $this->belongsTo(BillingPaymentMethod::class, 'payment_method_id'); }
    public function cashierSession(): BelongsTo { return $this->belongsTo(BillingCashierSession::class, 'cashier_session_id'); }
    public function receipt(): HasOne { return $this->hasOne(BillingReceipt::class, 'payment_id'); }
    public function refunds(): HasMany { return $this->hasMany(BillingRefund::class, 'payment_id'); }
    public function receivedBy(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    public function cancelledBy(): BelongsTo { return $this->belongsTo(User::class, 'cancelled_by'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }

    public function isMutable(): bool { return $this->status === 'pending'; }
    public function isCompleted(): bool { return $this->status === 'completed'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }
}
