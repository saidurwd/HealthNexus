<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class TaxEntry extends Model
{
    protected $table = 'fin_tax_entries';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['entry_date' => 'date:Y-m-d', 'base_amount' => 'decimal:2', 'tax_amount' => 'decimal:2'];
}
