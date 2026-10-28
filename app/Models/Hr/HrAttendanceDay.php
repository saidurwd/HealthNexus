<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrAttendanceDay extends Model
{
    protected $table = 'hr_attendance_days';

    protected $guarded = ['id'];

    protected $casts = ['work_date' => 'date:Y-m-d', 'first_in' => 'datetime', 'last_out' => 'datetime', 'locked' => 'boolean'];

    /** @return BelongsTo<HrEmployee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    /** @return BelongsTo<HrShift, $this> */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(HrShift::class, 'shift_id');
    }
}
