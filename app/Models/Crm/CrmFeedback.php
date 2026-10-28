<?php

namespace App\Models\Crm;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmFeedback extends Model
{
    use HasPublicId;

    protected $table = 'crm_feedback';

    protected $guarded = ['id'];

    protected $casts = ['rating' => 'integer', 'nps' => 'integer'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<Provider, $this> */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
