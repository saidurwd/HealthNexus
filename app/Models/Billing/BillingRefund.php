<?php

namespace App\Models\Billing;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingRefund extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'branch_id', 'payment_id', 'invoice_id', 'patient_id', 'refund_number', 'amount', 'reason', 'status', 'requested_by', 'requested_at', 'approved_by', 'approved_at', 'approval_note', 'processed_by', 'processed_at', 'processor_reference'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'requested_at' => 'datetime', 'approved_at' => 'datetime', 'processed_at' => 'datetime'];
    }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function payment(): BelongsTo { return $this->belongsTo(BillingPayment::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(BillingInvoice::class); }
    public function patient(): BelongsTo { return $this->belongsTo(\App\Models\Patient::class); }
    public function requestedBy(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function processedBy(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }

    public function isApproved(): bool { return in_array($this->status, ['approved', 'processed'], true); }
    public function isProcessed(): bool { return $this->status === 'processed'; }
    public function canApprove(): bool { return $this->status === 'requested'; }
}
