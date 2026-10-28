<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrSalaryStructureLine extends Model
{
    protected $table = 'hr_salary_structure_lines';

    protected $guarded = ['id'];

    protected $casts = ['amount' => 'decimal:2', 'percent' => 'decimal:4'];

    /** @return BelongsTo<HrPayComponent, $this> */
    public function component(): BelongsTo
    {
        return $this->belongsTo(HrPayComponent::class, 'component_id');
    }
}
