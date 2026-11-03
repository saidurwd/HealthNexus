<?php

namespace App\Models\Finance;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class Payable extends Model
{
    use HasPublicId;

    protected $table = 'fin_payables';

    protected $guarded = ['id'];

    protected $casts = ['invoice_date' => 'date:Y-m-d', 'due_date' => 'date:Y-m-d', 'amount' => 'decimal:2', 'settled' => 'decimal:2'];
}
