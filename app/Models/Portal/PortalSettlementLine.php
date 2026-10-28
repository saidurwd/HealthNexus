<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

class PortalSettlementLine extends Model
{
    protected $table = 'portal_settlement_lines';

    protected $guarded = ['id'];

    protected $casts = ['amount' => 'decimal:2', 'fee' => 'decimal:2', 'paid_on' => 'date:Y-m-d'];
}
