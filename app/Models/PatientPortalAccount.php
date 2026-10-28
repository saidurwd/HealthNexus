<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use App\Models\Portal\PortalDevice;
use App\Models\Portal\PortalProxy;
use Database\Factories\PatientPortalAccountFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

/**
 * The patient's login (Phase 17; the table was made in Phase 1). A different guard from staff: `portal` for the light web pages, Sanctum tokens with the ability `portal`
 * for the mobile app. One account per patient; a relative's access is a PortalProxy row, not a second login on the same patient.
 */
class PatientPortalAccount extends Model implements AuthenticatableContract
{
    use Authenticatable, HasApiTokens, HasFactory, HasPublicId;

    protected $fillable = [
        'organization_id', 'patient_id', 'user_id', 'email', 'is_active', 'invited_at', 'activated_at',
        'phone', 'password', 'status', 'failed_attempts', 'locked_until', 'last_login_at', 'last_login_ip', 'mfa_required', 'language',
        'invitation_hash', 'invitation_expires_at', 'terms_accepted_at', 'terms_version', 'closed_at', 'closure_reason',
    ];

    protected $hidden = ['password', 'remember_token', 'invitation_hash'];

    protected $casts = [
        'is_active' => 'boolean',
        'mfa_required' => 'boolean',
        'password' => 'hashed',
        'invited_at' => 'datetime',
        'activated_at' => 'datetime',
        'locked_until' => 'datetime',
        'last_login_at' => 'datetime',
        'invitation_expires_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function newFactory(): PatientPortalAccountFactory
    {
        return PatientPortalAccountFactory::new();
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<PortalDevice, $this> */
    public function devices(): HasMany
    {
        return $this->hasMany(PortalDevice::class, 'account_id');
    }

    /** Relatives this account may act for (active, in date). */
    /** @return HasMany<PortalProxy, $this> */
    public function proxies(): HasMany
    {
        return $this->hasMany(PortalProxy::class, 'account_id');
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function canLogin(): bool
    {
        return $this->status === 'active' && ! $this->isLocked() && $this->password !== null;
    }
}
