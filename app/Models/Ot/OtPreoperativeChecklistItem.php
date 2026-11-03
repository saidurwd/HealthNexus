<?php

namespace App\Models\Ot;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtPreoperativeChecklistItem extends Model
{
    use HasPublicId;

    protected $fillable = [
        'checklist_id', 'category', 'label', 'response', 'is_required', 'is_applicable',
        'completed_by', 'completed_at', 'exception_reason',
    ];

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'is_applicable' => 'boolean', 'completed_at' => 'datetime'];
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(OtPreoperativeChecklist::class, 'checklist_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
