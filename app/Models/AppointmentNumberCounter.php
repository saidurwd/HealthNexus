<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentNumberCounter extends Model
{
    protected $fillable = [
        'organization_id',
        'branch_id',
        'counter_type',
        'last_number',
    ];

    protected $casts = [
        'last_number' => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
