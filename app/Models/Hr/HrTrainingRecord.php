<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrTrainingRecord extends Model
{
    protected $table = 'hr_training_records';

    protected $guarded = ['id'];

    protected $casts = ['valid_until' => 'date:Y-m-d'];

    /** @return BelongsTo<HrTraining, $this> */
    public function training(): BelongsTo
    {
        return $this->belongsTo(HrTraining::class, 'training_id');
    }

    /** @return BelongsTo<HrEmployee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }
}
