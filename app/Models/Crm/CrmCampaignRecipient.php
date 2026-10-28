<?php

namespace App\Models\Crm;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmCampaignRecipient extends Model
{
    protected $table = 'crm_campaign_recipients';

    protected $guarded = ['id'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
