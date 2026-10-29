<?php

namespace App\Models\Integration;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationMessage extends Model
{
    use HasPublicId;

    protected $table = 'integration_messages';

    protected $guarded = ['id'];

    protected $casts = ['payload' => 'encrypted', 'response' => 'encrypted', 'provenance' => 'array', 'next_attempt_at' => 'datetime', 'processed_at' => 'datetime', 'dead_at' => 'datetime', 'purged_at' => 'datetime'];

    /** @return BelongsTo<IntegrationPartner, $this> */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(IntegrationPartner::class);
    }
}
