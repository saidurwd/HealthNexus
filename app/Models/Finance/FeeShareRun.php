<?php

namespace App\Models\Finance;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class FeeShareRun extends Model
{
    use HasPublicId;

    protected $table = 'fin_fee_share_runs';

    protected $guarded = ['id'];

    protected $casts = ['revenue_base' => 'decimal:2', 'amount' => 'decimal:2'];
}
