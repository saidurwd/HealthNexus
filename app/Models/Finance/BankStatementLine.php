<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class BankStatementLine extends Model
{
    protected $table = 'fin_bank_statement_lines';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['txn_date' => 'date:Y-m-d', 'money_in' => 'decimal:2', 'money_out' => 'decimal:2', 'matched_at' => 'datetime'];
}
