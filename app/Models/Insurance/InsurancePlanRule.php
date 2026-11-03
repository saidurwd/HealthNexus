<?php

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsurancePlanRule extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = ['copay_percent' => 'decimal:2', 'limit_amount' => 'decimal:2', 'requires_preauth' => 'boolean'];
}
