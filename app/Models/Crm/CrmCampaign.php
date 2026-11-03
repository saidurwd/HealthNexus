<?php

namespace App\Models\Crm;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmCampaign extends Model
{
    use HasPublicId;

    protected $table = 'crm_campaigns';

    protected $guarded = ['id'];

    protected $casts = ['stats' => 'array', 'scheduled_at' => 'datetime', 'approved_at' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime'];

    /** @return BelongsTo<CrmSegment, $this> */
    public function segment(): BelongsTo
    {
        return $this->belongsTo(CrmSegment::class, 'segment_id');
    }
}
