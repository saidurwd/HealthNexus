<?php

namespace App\Models\Portal;

use App\Models\Patient;
use App\Models\PatientPortalAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalAccessLog extends Model
{
    protected $table = 'portal_access_log';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['as_proxy' => 'boolean', 'created_at' => 'datetime'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<PatientPortalAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(PatientPortalAccount::class, 'account_id');
    }
}
