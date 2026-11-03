<?php

namespace App\Models\Portal;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalServiceRequest extends Model
{
    use HasPublicId;

    protected $table = 'portal_service_requests';

    protected $guarded = ['id'];

    protected $casts = ['due_on' => 'date:Y-m-d', 'closed_at' => 'datetime'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
