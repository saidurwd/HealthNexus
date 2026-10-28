<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class HrLoanEntry extends Model
{
    protected $table = 'hr_loan_entries';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['amount' => 'decimal:2'];
}
