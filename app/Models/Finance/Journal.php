<?php

namespace App\Models\Finance;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A set of lines that balance. Once posted it is never changed or deleted: a mistake is reversed by a second journal that points back. */
class Journal extends Model
{
    use HasPublicId;

    protected $table = 'fin_journals';

    protected $guarded = ['id'];

    protected $casts = ['journal_date' => 'date:Y-m-d', 'submitted_at' => 'datetime', 'posted_at' => 'datetime', 'total_debit' => 'decimal:2', 'total_credit' => 'decimal:2'];

    /** @return HasMany<JournalLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'journal_id')->orderBy('line_no');
    }

    /** @return BelongsTo<Period, $this> */
    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class, 'period_id');
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }
}
