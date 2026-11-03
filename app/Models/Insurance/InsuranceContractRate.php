<?php

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceContractRate extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = ['agreed_rate' => 'decimal:2', 'effective_from' => 'date', 'effective_to' => 'date'];
}
