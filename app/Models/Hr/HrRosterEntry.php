<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrRosterEntry extends Model
{
    protected $table = 'hr_roster_entries';

    protected $guarded = ['id'];

    protected $casts = ['work_date' => 'date:Y-m-d'];

    /** @return BelongsTo<HrShift, $this> */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(HrShift::class, 'shift_id');
    }

    /** @return BelongsTo<HrEmployee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }
}
