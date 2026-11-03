<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One debit or credit line of a journal. */
class JournalLine extends Model
{
    protected $table = 'fin_journal_lines';

    protected $guarded = ['id'];

    protected $casts = ['journal_date' => 'date:Y-m-d', 'debit' => 'decimal:2', 'credit' => 'decimal:2'];

    public $timestamps = false;

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /** @return BelongsTo<Journal, $this> */
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }
}
