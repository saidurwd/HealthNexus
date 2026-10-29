<?php

namespace App\Models\Finance;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatement extends Model
{
    use HasPublicId;

    protected $table = 'fin_bank_statements';

    protected $guarded = ['id'];

    protected $casts = ['period_from' => 'date:Y-m-d', 'period_to' => 'date:Y-m-d', 'opening_balance' => 'decimal:2', 'closing_balance' => 'decimal:2'];

    /** @return BelongsTo<BankAccount, $this> */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }
}
