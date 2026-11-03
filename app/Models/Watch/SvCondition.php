<?php

namespace App\Models\Watch;

use Database\Factories\Watch\SvConditionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SvCondition extends Model
{
    use HasFactory;

    protected static function newFactory(): SvConditionFactory
    {
        return SvConditionFactory::new();
    }

    protected $table = 'sv_conditions';

    protected $guarded = ['id'];

    protected $casts = ['icd_prefixes' => 'array', 'active' => 'boolean', 'spike_factor' => 'decimal:1'];

    /** @return HasMany<SvSignal, $this> */
    public function signals(): HasMany
    {
        return $this->hasMany(SvSignal::class, 'condition_id');
    }
}
