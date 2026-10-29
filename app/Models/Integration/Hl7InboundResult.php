<?php

namespace App\Models\Integration;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hl7InboundResult extends Model
{
    use HasPublicId;

    protected $table = 'hl7_inbound_results';

    protected $guarded = ['id'];

    protected $casts = ['observed_at' => 'datetime', 'reviewed_at' => 'datetime'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
