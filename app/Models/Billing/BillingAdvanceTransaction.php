<?php

namespace App\Models\Billing;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingAdvanceTransaction extends Model
{
    use HasFactory;

    protected $fillable = ['advance_account_id', 'payment_id', 'invoice_id', 'type', 'amount', 'balance_after', 'reference_type', 'reference_id', 'idempotency_key', 'performed_by', 'notes'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'balance_after' => 'decimal:2'];
    }

    public function advanceAccount(): BelongsTo { return $this->belongsTo(BillingAdvanceAccount::class, 'advance_account_id'); }
    public function payment(): BelongsTo { return $this->belongsTo(BillingPayment::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(BillingInvoice::class); }
    public function performedBy(): BelongsTo { return $this->belongsTo(User::class, 'performed_by'); }

    /**
     * This table carries no company_id/branch_id columns of its own — tenant scope is inherited
     * from the parent advance account, so this filters via that relation instead of a literal column.
     */
    public function scopeForTenant($query, int $companyId, ?int $branchId = null)
    {
        return $query->whereHas('advanceAccount', fn ($q) => $q->forTenant($companyId, $branchId));
    }
}
