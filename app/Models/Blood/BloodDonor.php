<?php

namespace App\Models\Blood;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BloodDonor extends Model
{
    use HasFactory, HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['deferred_until' => 'date', 'consent_at' => 'datetime', 'last_donation_at' => 'datetime', 'is_replacement' => 'boolean'];

    public const ELIGIBLE = 'eligible';

    public const DEFERRED = 'deferred';

    public const PERMANENTLY_DEFERRED = 'permanently_deferred';

    public const INACTIVE = 'inactive';

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return HasMany<BloodDonation, $this> */
    public function donations(): HasMany
    {
        return $this->hasMany(BloodDonation::class, 'donor_id');
    }

    /** True while a deferral (temporary or permanent) is in force. */
    public function isDeferred(): bool
    {
        return $this->status === self::PERMANENTLY_DEFERRED || ($this->status === self::DEFERRED && ($this->deferred_until === null || $this->deferred_until->gte(today())));
    }
}
