<?php

namespace App\Models\Blood;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BloodDonorScreening extends Model
{
    use HasFactory, HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['questionnaire' => 'array', 'failed_rules' => 'array', 'screened_at' => 'datetime', 'deferred_until' => 'date'];

    /** @return BelongsTo<BloodDonor, $this> */
    public function donor(): BelongsTo
    {
        return $this->belongsTo(BloodDonor::class, 'donor_id');
    }
}
