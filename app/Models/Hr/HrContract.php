<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrContract extends Model
{
    protected $table = 'hr_contracts';

    protected $guarded = ['id'];

    protected $casts = ['start_on' => 'date:Y-m-d', 'end_on' => 'date:Y-m-d'];

    /** @return BelongsTo<HrEmployee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }
}
