<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcurementQuotationLine extends Model
{
    use HasFactory;

    protected $table = 'procurement_quotation_lines';

    protected $guarded = ['id'];

    protected $casts = ['unit_price' => 'decimal:4'];
}
