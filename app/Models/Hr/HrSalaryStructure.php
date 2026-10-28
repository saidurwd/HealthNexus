<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrSalaryStructure extends Model
{
    protected $table = 'hr_salary_structures';

    protected $guarded = ['id'];

    protected $casts = ['effective_from' => 'date:Y-m-d', 'effective_to' => 'date:Y-m-d'];

    /** @return HasMany<HrSalaryStructureLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(HrSalaryStructureLine::class, 'structure_id');
    }

    /** @return BelongsTo<HrEmployee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }
}
