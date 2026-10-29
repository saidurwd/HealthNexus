<?php

namespace App\Models\Finance;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Finance\BankAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory, HasPublicId;

    protected static function newFactory(): BankAccountFactory
    {
        return BankAccountFactory::new();
    }

    protected $table = 'fin_bank_accounts';

    protected $guarded = ['id'];

    protected $casts = ['active' => 'boolean'];
}
