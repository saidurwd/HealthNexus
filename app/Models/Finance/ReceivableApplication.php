<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class ReceivableApplication extends Model
{
    protected $table = 'fin_receivable_applications';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['applied_on' => 'date:Y-m-d', 'amount' => 'decimal:2'];
}
