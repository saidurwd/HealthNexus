<?php

namespace App\Models\Hr;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrRegularization extends Model
{
    use HasPublicId;

    protected $table = 'hr_regularizations';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['work_date' => 'date:Y-m-d', 'requested_in' => 'datetime', 'requested_out' => 'datetime', 'decided_at' => 'datetime'];

    /** @return BelongsTo<HrEmployee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }
}
