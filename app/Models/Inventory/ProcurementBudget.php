<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcurementBudget extends Model
{
    use HasFactory;

    protected $table = 'procurement_budgets';

    protected $guarded = ['id'];

    protected $casts = ['period_start' => 'date', 'period_end' => 'date', 'amount' => 'decimal:2'];
}
