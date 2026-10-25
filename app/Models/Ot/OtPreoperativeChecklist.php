<?php

namespace App\Models\Ot;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OtPreoperativeChecklist extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = ['surgery_id', 'status', 'completed_at', 'completed_by'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(OtSurgery::class, 'surgery_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OtPreoperativeChecklistItem::class, 'checklist_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
