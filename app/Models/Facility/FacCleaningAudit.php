<?php

namespace App\Models\Facility;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacCleaningAudit extends Model
{
    use HasPublicId;

    protected $table = 'fac_cleaning_audits';

    protected $guarded = ['id'];

    protected $casts = ['audited_on' => 'date:Y-m-d', 'findings' => 'array', 'score' => 'decimal:2'];

    /** @return BelongsTo<User, $this> */
    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }
}
