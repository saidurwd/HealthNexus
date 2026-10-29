<?php

namespace App\Models\Finance;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasPublicId;

    protected $table = 'fin_budgets';

    protected $guarded = ['id'];

    protected $casts = ['approved_at' => 'datetime'];
}
