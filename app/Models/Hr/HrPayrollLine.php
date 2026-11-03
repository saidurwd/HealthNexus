<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrPayrollLine extends Model
{
    protected $table = 'hr_payroll_lines';

    protected $guarded = ['id'];

    protected $casts = ['gross' => 'decimal:2', 'deductions' => 'decimal:2', 'tax' => 'decimal:2', 'net' => 'decimal:2', 'employer' => 'decimal:2', 'inputs' => 'array', 'exceptions' => 'array'];

    /** @return HasMany<HrPayrollLineItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(HrPayrollLineItem::class, 'line_id');
    }

    /** @return BelongsTo<HrEmployee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    /** @return BelongsTo<HrPayrollRun, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(HrPayrollRun::class, 'run_id');
    }
}
