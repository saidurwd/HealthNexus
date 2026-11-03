<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargeEventLog extends Model
{
    protected $table = 'charge_event_log';

    public const OUTCOME_ACCEPTED = 'accepted';

    public const OUTCOME_REJECTED = 'rejected';

    public const OUTCOME_NOT_CHARGEABLE = 'not_chargeable';

    public const OUTCOME_REVERSED = 'reversed';

    public const OUTCOME_REVERSAL_NEEDS_CREDIT_NOTE = 'reversal_needs_credit_note';

    protected $guarded = ['id'];

    protected $casts = ['is_chargeable' => 'boolean', 'quantity' => 'float', 'performed_at' => 'datetime'];

    public function charge(): BelongsTo
    {
        return $this->belongsTo(BillingCharge::class, 'billing_charge_id');
    }
}
