<?php

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceRemittanceLine extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = ['paid_amount' => 'decimal:2', 'adjustment_amount' => 'decimal:2'];
}
