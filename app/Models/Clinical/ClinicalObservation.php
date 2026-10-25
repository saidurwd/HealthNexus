<?php

namespace App\Models\Clinical;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalObservation extends Model
{
    use HasPublicId;

    public const STATUS_PRELIMINARY = 'preliminary';

    public const STATUS_FINAL = 'final';

    public const STATUS_AMENDED = 'amended';

    public const STATUS_ENTERED_IN_ERROR = 'entered_in_error';

    protected $guarded = ['id', 'public_id'];

    protected $casts = [
        'value_number' => 'float',
        'value_boolean' => 'boolean',
        'reference_low' => 'float',
        'reference_high' => 'float',
        'effective_at' => 'datetime',
        'recorded_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_id');
    }
}
