<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class HrLeaveLedger extends Model
{
    protected $table = 'hr_leave_ledger';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['days' => 'decimal:2', 'effective_on' => 'date:Y-m-d'];
}
