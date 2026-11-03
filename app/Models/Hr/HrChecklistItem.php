<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrChecklistItem extends Model
{
    protected $table = 'hr_checklist_items';

    protected $guarded = ['id'];

    protected $casts = ['required' => 'boolean', 'done_at' => 'datetime'];

    /** @return BelongsTo<HrChecklist, $this> */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(HrChecklist::class, 'checklist_id');
    }
}
