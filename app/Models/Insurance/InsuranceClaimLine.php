<?php

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceClaimLine extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = ['gross_amount' => 'decimal:2', 'contract_amount' => 'decimal:2', 'non_covered_amount' => 'decimal:2', 'deductible_amount' => 'decimal:2', 'copay_amount' => 'decimal:2', 'payer_share' => 'decimal:2', 'patient_share' => 'decimal:2', 'paid_amount' => 'decimal:2'];
}
