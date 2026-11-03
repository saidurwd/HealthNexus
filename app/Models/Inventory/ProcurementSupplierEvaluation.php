<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcurementSupplierEvaluation extends Model
{
    use HasFactory;

    protected $table = 'procurement_supplier_evaluations';

    protected $guarded = ['id'];

    protected $casts = ['period_end' => 'date', 'delivery_score' => 'decimal:2', 'quality_score' => 'decimal:2', 'price_score' => 'decimal:2', 'total_score' => 'decimal:2'];
}
