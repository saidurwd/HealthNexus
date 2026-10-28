<?php

namespace App\Models\Crm;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmInteraction extends Model
{
    use HasPublicId;

    protected $table = 'crm_interactions';

    protected $guarded = ['id'];

    protected $casts = ['follow_up_on' => 'date:Y-m-d'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
