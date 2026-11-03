<?php

namespace App\Models\Portal;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalResultRelease extends Model
{
    use HasPublicId;

    protected $table = 'portal_result_releases';

    protected $guarded = ['id'];

    protected $casts = ['critical' => 'boolean', 'due_at' => 'datetime', 'released_at' => 'datetime', 'reported_at' => 'datetime'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
