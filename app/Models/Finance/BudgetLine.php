<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class BudgetLine extends Model
{
    protected $table = 'fin_budget_lines';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['amount' => 'decimal:2'];
}
