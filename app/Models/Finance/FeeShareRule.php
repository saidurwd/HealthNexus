<?php

namespace App\Models\Finance;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class FeeShareRule extends Model
{
    use HasPublicId;

    protected $table = 'fin_fee_share_rules';

    protected $guarded = ['id'];

    protected $casts = ['percent' => 'decimal:2', 'active' => 'boolean', 'effective_from' => 'date:Y-m-d', 'effective_to' => 'date:Y-m-d'];
}
