<?php

namespace App\Models\Quality;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaInvestigation extends Model
{
    use HasPublicId;

    protected $table = 'qa_investigations';

    protected $guarded = ['id'];

    protected $casts = ['root_causes' => 'array', 'started_at' => 'datetime', 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];

    /** @return BelongsTo<QaIncident, $this> */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(QaIncident::class, 'incident_id');
    }

    /** @return BelongsTo<User, $this> */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_id');
    }
}
