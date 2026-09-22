<?php

namespace App\Models\Billing;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingInvoice extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'branch_id', 'patient_id', 'encounter_id', 'corporate_id', 'corporate_contract_id', 'insurance_policy_id', 'invoice_number', 'invoice_type', 'status', 'currency', 'patient_category', 'subtotal', 'discount_type', 'discount_amount', 'tax_amount', 'rounding_amount', 'grand_total', 'paid_amount', 'due_amount', 'invoice_date', 'due_date', 'billing_party_type', 'billing_party_id', 'notes', 'version', 'created_by', 'finalized_by', 'finalized_at', 'cancelled_by', 'cancelled_at', 'cancellation_reason', 'refunded_by', 'refunded_at', 'written_off_by', 'written_off_at'];

    protected function casts(): array
    {
        return ['subtotal' => 'decimal:2', 'discount_amount' => 'decimal:2', 'tax_amount' => 'decimal:2', 'rounding_amount' => 'decimal:2', 'grand_total' => 'decimal:2', 'paid_amount' => 'decimal:2', 'due_amount' => 'decimal:2', 'invoice_date' => 'date', 'due_date' => 'date', 'version' => 'integer', 'finalized_at' => 'datetime', 'cancelled_at' => 'datetime', 'refunded_at' => 'datetime', 'written_off_at' => 'datetime'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function patient(): BelongsTo { return $this->belongsTo(\App\Models\Patient::class); }
    public function encounter(): BelongsTo { return $this->belongsTo(\App\Models\Encounter::class); }
    public function corporate(): BelongsTo { return $this->belongsTo(BillingCorporate::class); }
    public function corporateContract(): BelongsTo { return $this->belongsTo(BillingCorporateContract::class); }
    public function insurancePolicy(): BelongsTo { return $this->belongsTo(BillingInsurancePolicy::class); }
    public function items(): HasMany { return $this->hasMany(BillingInvoiceItem::class, 'invoice_id'); }
    public function payments(): HasMany { return $this->hasMany(BillingPayment::class, 'invoice_id'); }
    public function refunds(): HasMany { return $this->hasMany(BillingRefund::class, 'invoice_id'); }
    public function adjustments(): HasMany { return $this->hasMany(BillingAdjustment::class, 'invoice_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function finalizedBy(): BelongsTo { return $this->belongsTo(User::class, 'finalized_by'); }
    public function cancelledBy(): BelongsTo { return $this->belongsTo(User::class, 'cancelled_by'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }

    public function isMutable(): bool { return in_array($this->status, ['draft', 'pending'], true); }
    public function isFinalized(): bool { return $this->finalized_at !== null; }
    public function isPaid(): bool { return $this->status === 'paid'; }
    public function isPartiallyPaid(): bool { return $this->status === 'partially_paid'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }

    public function canTransitionTo(string $status): bool
    {
        $allowed = [
            'draft' => ['pending', 'cancelled'],
            'pending' => ['finalized', 'cancelled'],
            'finalized' => ['partially_paid', 'paid', 'cancelled', 'refunded', 'written_off'],
            'partially_paid' => ['paid', 'refunded', 'written_off'],
            'paid' => ['refunded'],
        ];

        return in_array($status, $allowed[$this->status] ?? [], true);
    }

    public function remainingDue(): string
    {
        return bcsub((string) $this->grand_total, (string) $this->paid_amount, 2);
    }
}
