<?php

namespace App\Models\Blood;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BloodReservation extends Model
{
    use HasFactory, HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['reserved_until' => 'datetime', 'released_at' => 'datetime'];

    /** @return BelongsTo<BloodUnit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(BloodUnit::class, 'unit_id');
    }
}
