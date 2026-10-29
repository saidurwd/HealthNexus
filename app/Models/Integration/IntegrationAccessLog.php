<?php

namespace App\Models\Integration;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationAccessLog extends Model
{
    protected $table = 'integration_access_log';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['created_at' => 'datetime'];

    /** @return BelongsTo<IntegrationPartner, $this> */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(IntegrationPartner::class);
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
