<?php

namespace App\Models\Hr;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrLeaveRequest extends Model
{
    use HasPublicId;

    protected $table = 'hr_leave_requests';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['from_date' => 'date:Y-m-d', 'to_date' => 'date:Y-m-d', 'days' => 'decimal:2', 'half_day' => 'boolean', 'manager_decided_at' => 'datetime', 'hr_decided_at' => 'datetime'];

    /** @return BelongsTo<HrEmployee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    /** @return BelongsTo<HrLeaveType, $this> */
    public function type(): BelongsTo
    {
        return $this->belongsTo(HrLeaveType::class, 'leave_type_id');
    }
}
