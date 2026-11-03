<?php

namespace App\Models\Blood;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BloodUnit extends Model
{
    use HasFactory, HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['collected_at' => 'datetime', 'expires_at' => 'datetime', 'released_at' => 'datetime', 'is_external' => 'boolean'];

    public const QUARANTINED = 'quarantined';

    public const AVAILABLE = 'available';

    public const RESERVED = 'reserved';

    public const ISSUED = 'issued';

    public const TRANSFUSED = 'transfused';

    public const DISCARDED = 'discarded';

    public const EXPIRED = 'expired';

    public const BLOCKED = 'blocked';

    public const PROCESSED = 'processed';

    /** @return BelongsTo<BloodDonation, $this> */
    public function donation(): BelongsTo
    {
        return $this->belongsTo(BloodDonation::class, 'donation_id');
    }

    /** @return BelongsTo<BloodStorageLocation, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(BloodStorageLocation::class, 'location_id');
    }

    /** @return HasMany<BloodUnitEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(BloodUnitEvent::class, 'unit_id')->orderBy('occurred_at')->orderBy('id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->lte(now());
    }

    /** Red cells (whole blood, packed cells) are matched on ABO + Rh of the red cell; plasma and platelets differently. */
    public function kind(): string
    {
        return config("blood.components.{$this->component}.kind", 'red');
    }
}
