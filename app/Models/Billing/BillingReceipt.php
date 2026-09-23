<?php

namespace App\Models\Billing;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingReceipt extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'branch_id', 'payment_id', 'invoice_id', 'receipt_number', 'status', 'issued_at', 'issued_by', 'voided_at', 'voided_by', 'voided_reason'];

    protected function casts(): array { return ['issued_at' => 'datetime', 'voided_at' => 'datetime']; }

    public function company(): BelongsTo { return $this->belongsTo(\App\Models\Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(\App\Models\Branch::class); }
    public function payment(): BelongsTo { return $this->belongsTo(BillingPayment::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(BillingInvoice::class); }
    public function issuedBy(): BelongsTo { return $this->belongsTo(User::class, 'issued_by'); }
    public function voidedBy(): BelongsTo { return $this->belongsTo(User::class, 'voided_by'); }

    public function isVoided(): bool { return $this->status === 'voided'; }

    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->where('company_id', $companyId)->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }
}
