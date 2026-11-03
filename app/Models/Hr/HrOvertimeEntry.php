<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrOvertimeEntry extends Model
{
    protected $table = 'hr_overtime_entries';

    protected $guarded = ['id'];

    protected $casts = ['work_date' => 'date:Y-m-d', 'decided_at' => 'datetime'];

    /** @return BelongsTo<HrEmployee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }
}
