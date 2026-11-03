<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrAppraisal extends Model
{
    protected $table = 'hr_appraisals';

    protected $guarded = ['id'];

    protected $casts = ['submitted_at' => 'datetime', 'acknowledged_at' => 'datetime'];

    /** @return BelongsTo<HrEmployee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    /** @return BelongsTo<HrAppraisalCycle, $this> */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(HrAppraisalCycle::class, 'cycle_id');
    }
}
