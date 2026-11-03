<?php

namespace App\Models\Ot;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtRecoveryRecord extends Model
{
    use HasPublicId;

    public const STATUS_IN_RECOVERY = 'in_recovery';

    public const STATUS_READY_FOR_TRANSFER = 'ready_for_transfer';

    public const STATUS_TRANSFERRED = 'transferred';

    public const STATUS_DISCHARGED = 'discharged';

    public const DESTINATION_WARD = 'ward';

    public const DESTINATION_ICU = 'icu';

    public const DESTINATION_HDU = 'hdu';

    public const DESTINATION_DISCHARGE = 'discharge';

    public const DESTINATION_OTHER_FACILITY = 'other_facility';

    protected $fillable = [
        'surgery_id', 'patient_id', 'arrived_at', 'status', 'pain_score', 'consciousness',
        'airway_status', 'nausea_vomiting', 'complications', 'discharge_criteria_met',
        'destination', 'transferred_at', 'transferred_by', 'discharged_at',
    ];

    protected function casts(): array
    {
        return [
            'arrived_at' => 'datetime',
            'discharge_criteria_met' => 'boolean',
            'transferred_at' => 'datetime',
            'discharged_at' => 'datetime',
        ];
    }

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(OtSurgery::class, 'surgery_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
}
