<?php

namespace App\Models\Finance;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankDeposit extends Model
{
    use HasPublicId;

    protected $table = 'fin_bank_deposits';

    protected $guarded = ['id'];

    protected $casts = ['deposit_date' => 'date:Y-m-d', 'amount' => 'decimal:2'];

    /** @return BelongsTo<BankAccount, $this> */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }
}
