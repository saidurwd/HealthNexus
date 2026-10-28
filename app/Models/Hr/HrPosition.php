<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrPosition extends Model
{
    protected $table = 'hr_positions';

    protected $guarded = ['id'];

    /** @return BelongsTo<HrJob, $this> */
    public function job(): BelongsTo
    {
        return $this->belongsTo(HrJob::class, 'job_id');
    }
}
