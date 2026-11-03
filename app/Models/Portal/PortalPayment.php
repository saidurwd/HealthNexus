<?php

namespace App\Models\Portal;

use App\Models\Billing\BillingInvoice;
use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalPayment extends Model
{
    use HasPublicId;

    protected $table = 'portal_payments';

    protected $guarded = ['id'];

    protected $casts = ['amount' => 'decimal:2', 'initiated_at' => 'datetime', 'completed_at' => 'datetime', 'reconciled_at' => 'datetime'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<BillingInvoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class, 'invoice_id');
    }
}
