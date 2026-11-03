<?php

namespace App\Models\Ed;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdObservationStay extends Model
{
    use HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['started_at' => 'datetime', 'ended_at' => 'datetime'];

    /** @return BelongsTo<EdVisit, $this> */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(EdVisit::class, 'visit_id');
    }
}
