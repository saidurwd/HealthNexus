<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrCredential extends Model
{
    protected $table = 'hr_credentials';

    protected $guarded = ['id'];

    protected $casts = ['issued_on' => 'date:Y-m-d', 'expires_on' => 'date:Y-m-d', 'verified_at' => 'datetime'];

    /** @return BelongsTo<HrEmployee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }
}
