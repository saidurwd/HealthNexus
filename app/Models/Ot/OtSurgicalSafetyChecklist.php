<?php

namespace App\Models\Ot;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OtSurgicalSafetyChecklist extends Model
{
    use HasPublicId;

    public const PHASE_SIGN_IN = 'sign_in';

    public const PHASE_TIME_OUT = 'time_out';

    public const PHASE_SIGN_OUT = 'sign_out';

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = ['surgery_id', 'phase', 'status', 'completed_by', 'completed_at'];

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
        return $this->hasMany(OtSurgicalSafetyChecklistItem::class, 'checklist_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
