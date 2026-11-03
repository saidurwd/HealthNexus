<?php

namespace App\Models\Quality;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaComplaintLink extends Model
{
    protected $table = 'qa_complaint_links';

    protected $guarded = ['id'];

    public const UPDATED_AT = null;

    /** @return BelongsTo<QaIncident, $this> */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(QaIncident::class, 'incident_id');
    }
}
