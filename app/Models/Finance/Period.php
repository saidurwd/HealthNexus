<?php

namespace App\Models\Finance;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A month of a fiscal year. `locked` refuses new postings; `closed` is final with the year. */
class Period extends Model
{
    use HasPublicId;

    protected $table = 'fin_periods';

    protected $guarded = ['id'];

    protected $casts = ['starts_on' => 'date:Y-m-d', 'ends_on' => 'date:Y-m-d', 'locked_at' => 'datetime'];

    /** @return BelongsTo<FiscalYear, $this> */
    public function year(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
