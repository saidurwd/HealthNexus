<?php

namespace App\Models\Hr;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrPayrollRun extends Model
{
    use HasPublicId;

    protected $table = 'hr_payroll_runs';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['total_gross' => 'decimal:2', 'total_deductions' => 'decimal:2', 'total_tax' => 'decimal:2', 'total_net' => 'decimal:2', 'total_employer' => 'decimal:2', 'approved_at' => 'datetime', 'payslips_published_at' => 'datetime', 'journal' => 'array'];

    /** @return BelongsTo<HrPayrollPeriod, $this> */
    public function period(): BelongsTo
    {
        return $this->belongsTo(HrPayrollPeriod::class, 'period_id');
    }

    /** @return HasMany<HrPayrollLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(HrPayrollLine::class, 'run_id');
    }
}
