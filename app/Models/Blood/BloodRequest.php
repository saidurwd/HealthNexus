<?php

namespace App\Models\Blood;

use App\Models\Concerns\HasPublicId;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BloodRequest extends Model
{
    use HasFactory, HasPublicId;

    protected $guarded = ['id', 'public_id'];

    protected $casts = ['required_by' => 'datetime', 'requested_at' => 'datetime', 'replacement_donor_offered' => 'boolean'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return HasMany<BloodSample, $this> */
    public function samples(): HasMany
    {
        return $this->hasMany(BloodSample::class, 'request_id');
    }

    /** @return HasMany<BloodReservation, $this> */
    public function reservations(): HasMany
    {
        return $this->hasMany(BloodReservation::class, 'request_id');
    }

    /** @return HasMany<BloodIssue, $this> */
    public function issues(): HasMany
    {
        return $this->hasMany(BloodIssue::class, 'request_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['requested', 'sample_received', 'ready', 'partially_issued'], true);
    }
}
