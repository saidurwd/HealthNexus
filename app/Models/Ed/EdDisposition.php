<?php

namespace App\Models\Ed;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdDisposition extends Model
{
    use HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['details' => 'array', 'decided_at' => 'datetime', 'ambulance_required' => 'boolean'];

    /** @return BelongsTo<EdVisit, $this> */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(EdVisit::class, 'visit_id');
    }
}
