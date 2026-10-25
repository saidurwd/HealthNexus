<?php

namespace App\Models\Ot;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtSurgicalCount extends Model
{
    public const STAGE_INITIAL = 'initial';

    public const STAGE_ADDITIONAL = 'additional';

    public const STAGE_FINAL = 'final';

    protected $fillable = [
        'surgery_id', 'count_type', 'stage', 'count_value', 'counted_by', 'counted_at',
        'discrepancy', 'discrepancy_notes',
    ];

    protected function casts(): array
    {
        return ['counted_at' => 'datetime', 'discrepancy' => 'boolean'];
    }

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(OtSurgery::class, 'surgery_id');
    }

    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }
}
