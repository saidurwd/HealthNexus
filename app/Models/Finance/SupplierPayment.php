<?php

namespace App\Models\Finance;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    use HasPublicId;

    protected $table = 'fin_supplier_payments';

    protected $guarded = ['id'];

    protected $casts = ['payment_date' => 'date:Y-m-d', 'gross_amount' => 'decimal:2', 'withheld_ait' => 'decimal:2', 'withheld_vat' => 'decimal:2', 'net_amount' => 'decimal:2', 'approved_at' => 'datetime', 'paid_at' => 'datetime'];
}
