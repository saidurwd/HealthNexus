<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrCandidate extends Model
{
    protected $table = 'hr_candidates';

    protected $guarded = ['id'];

    /** @return BelongsTo<HrVacancy, $this> */
    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(HrVacancy::class, 'vacancy_id');
    }
}
