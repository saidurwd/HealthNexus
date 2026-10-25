<?php

namespace App\Models\Ot;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtSurgicalComplication extends Model
{
    protected $fillable = [
        'surgery_id', 'complication', 'severity', 'phase', 'occurred_at', 'description',
        'action_taken', 'outcome', 'reported_by',
    ];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(OtSurgery::class, 'surgery_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
