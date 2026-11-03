<?php

namespace App\Models\Portal;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalRefillRequest extends Model
{
    use HasPublicId;

    protected $table = 'portal_refill_requests';

    protected $guarded = ['id'];

    protected $casts = ['reviewed_at' => 'datetime'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<Prescription, $this> */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }
}
