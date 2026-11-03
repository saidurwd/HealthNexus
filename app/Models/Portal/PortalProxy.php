<?php

namespace App\Models\Portal;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use App\Models\PatientPortalAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalProxy extends Model
{
    use HasPublicId;

    protected $table = 'portal_proxies';

    protected $guarded = ['id'];

    protected $casts = ['scopes' => 'array', 'requested_at' => 'datetime', 'decided_at' => 'datetime', 'valid_from' => 'date:Y-m-d', 'valid_to' => 'date:Y-m-d'];

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
