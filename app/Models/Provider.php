<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Generic scheduling participant — not every provider is a system login (spec: "do not assume
 * every provider is a normal system user"). user_id is the optional link back to an Employee/User
 * account; provider_type covers doctor/consultant/specialist/nurse/physiotherapist/etc.
 */
class Provider extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'user_id',
        'provider_code',
        'name',
        'provider_type',
        'department_id',
        'specialty_id',
        'license_number',
        'status',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function unavailability(): HasMany
    {
        return $this->hasMany(ProviderUnavailability::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
