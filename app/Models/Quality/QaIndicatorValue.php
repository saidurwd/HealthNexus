<?php

namespace App\Models\Quality;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaIndicatorValue extends Model
{
    protected $table = 'qa_indicator_values';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['period' => 'date:Y-m-d', 'numerator' => 'decimal:2', 'denominator' => 'decimal:2', 'value' => 'decimal:4', 'computed_at' => 'datetime'];

    /** @return BelongsTo<QaIndicator, $this> */
    public function indicator(): BelongsTo
    {
        return $this->belongsTo(QaIndicator::class, 'indicator_id');
    }
}
