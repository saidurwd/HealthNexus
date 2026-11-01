<?php

namespace App\Models\Quality;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Quality\QaIndicatorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QaIndicator extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): QaIndicatorFactory
    {
        return QaIndicatorFactory::new();
    }

    protected $table = 'qa_indicators';

    protected $guarded = ['id'];

    protected $casts = ['active' => 'boolean', 'target' => 'decimal:4'];

    /** @return HasMany<QaIndicatorValue, $this> */
    public function values(): HasMany
    {
        return $this->hasMany(QaIndicatorValue::class, 'indicator_id');
    }
}
