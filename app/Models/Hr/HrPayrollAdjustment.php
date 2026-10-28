<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrPayrollAdjustment extends Model
{
    protected $table = 'hr_payroll_adjustments';

    protected $guarded = ['id'];

    protected $casts = ['amount' => 'decimal:2'];

    /** @return BelongsTo<HrEmployee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }
}
