<?php

namespace App\Models\Welfare;

use App\Models\Billing\BillingAdjustment;
use App\Models\Billing\BillingInvoice;
use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Database\Factories\Welfare\WfApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WfApplication extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): WfApplicationFactory
    {
        return WfApplicationFactory::new();
    }

    protected $table = 'wf_applications';

    protected $guarded = ['id'];

    protected $casts = ['amount' => 'decimal:2', 'requested_at' => 'datetime', 'settled_at' => 'datetime'];

    /** @return BelongsTo<WfGrant, $this> */
    public function grant(): BelongsTo
    {
        return $this->belongsTo(WfGrant::class, 'grant_id');
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /** @return BelongsTo<BillingInvoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class, 'invoice_id');
    }

    /** @return BelongsTo<BillingAdjustment, $this> */
    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(BillingAdjustment::class, 'adjustment_id');
    }
}
