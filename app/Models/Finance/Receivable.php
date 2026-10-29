<?php

namespace App\Models\Finance;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class Receivable extends Model
{
    use HasPublicId;

    protected $table = 'fin_receivables';

    protected $guarded = ['id'];

    protected $casts = ['document_date' => 'date:Y-m-d', 'due_date' => 'date:Y-m-d', 'amount' => 'decimal:2', 'settled' => 'decimal:2', 'written_off' => 'decimal:2'];
}
