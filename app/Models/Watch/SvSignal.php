<?php

namespace App\Models\Watch;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Watch\SvSignalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SvSignal extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): SvSignalFactory
    {
        return SvSignalFactory::new();
    }

    protected $table = 'sv_signals';

    protected $guarded = ['id'];

    protected $casts = ['week_start' => 'date:Y-m-d', 'window_from' => 'date:Y-m-d', 'window_to' => 'date:Y-m-d', 'baseline' => 'decimal:1', 'decided_at' => 'datetime', 'reported_at' => 'datetime', 'raised_at' => 'datetime'];

    /** @return BelongsTo<SvCondition, $this> */
    public function condition(): BelongsTo
    {
        return $this->belongsTo(SvCondition::class, 'condition_id');
    }
}
