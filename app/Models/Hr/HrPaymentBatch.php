<?php

namespace App\Models\Hr;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrPaymentBatch extends Model
{
    use HasPublicId;

    protected $table = 'hr_payment_batches';

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['total' => 'decimal:2'];

    /** @return BelongsTo<HrPayrollRun, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(HrPayrollRun::class, 'run_id');
    }
}
