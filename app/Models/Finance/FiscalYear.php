<?php

namespace App\Models\Finance;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A financial year. Closing it sweeps the profit or loss into retained earnings and locks every period. */
class FiscalYear extends Model
{
    use HasPublicId;

    protected $table = 'fin_fiscal_years';

    protected $guarded = ['id'];

    protected $casts = ['starts_on' => 'date:Y-m-d', 'ends_on' => 'date:Y-m-d', 'closed_at' => 'datetime'];

    /** @return HasMany<Period, $this> */
    public function periods(): HasMany
    {
        return $this->hasMany(Period::class, 'fiscal_year_id')->orderBy('number');
    }
}
