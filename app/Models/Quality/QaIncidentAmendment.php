<?php

namespace App\Models\Quality;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaIncidentAmendment extends Model
{
    protected $table = 'qa_incident_amendments';

    protected $guarded = ['id'];

    public const UPDATED_AT = null;

    /** @return BelongsTo<User, $this> */
    public function amender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'amended_by');
    }
}
