<?php

namespace App\Models\Facility;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacGateLog extends Model
{
    protected $table = 'fac_gate_logs';

    protected $guarded = ['id'];

    public const UPDATED_AT = null;

    /** @return BelongsTo<FacVisitorPass, $this> */
    public function pass(): BelongsTo
    {
        return $this->belongsTo(FacVisitorPass::class, 'pass_id');
    }
}
