<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrChecklist extends Model
{
    protected $table = 'hr_checklists';

    protected $guarded = ['id'];

    protected $casts = ['completed_at' => 'datetime'];

    /** @return HasMany<HrChecklistItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(HrChecklistItem::class, 'checklist_id');
    }

    /** @return BelongsTo<HrEmployee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }
}
