<?php

namespace App\Models\Blood;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BloodDonation extends Model
{
    use HasFactory, HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['started_at' => 'datetime', 'collected_at' => 'datetime', 'observation_until' => 'datetime', 'observation_done_at' => 'datetime'];

    /** @return BelongsTo<BloodDonor, $this> */
    public function donor(): BelongsTo
    {
        return $this->belongsTo(BloodDonor::class, 'donor_id');
    }

    /** @return HasMany<BloodUnit, $this> */
    public function units(): HasMany
    {
        return $this->hasMany(BloodUnit::class, 'donation_id');
    }

    /** @return HasMany<BloodUnitTest, $this> */
    public function tests(): HasMany
    {
        return $this->hasMany(BloodUnitTest::class, 'donation_id');
    }
}
