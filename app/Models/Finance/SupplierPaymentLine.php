<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class SupplierPaymentLine extends Model
{
    protected $table = 'fin_supplier_payment_lines';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['amount' => 'decimal:2'];
}
