<?php

namespace App\Models\Hr;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrLoan extends Model
{
    use HasPublicId;

    protected $table = 'hr_loans';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['amount' => 'decimal:2', 'monthly_deduction' => 'decimal:2', 'balance' => 'decimal:2', 'start_month' => 'date:Y-m-d', 'approved_at' => 'datetime'];

    /** @return BelongsTo<HrEmployee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }
}
