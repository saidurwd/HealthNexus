<?php

namespace App\Models\Integration;

use App\Models\Concerns\HasPublicId;
use Database\Factories\Integration\IntegrationPartnerFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

/**
 * An outside system we exchange with: a hospital, a lab, a PACS, an insurer, the national exchange. It is also the "client" that holds Sanctum tokens for the FHIR API
 * (abilities `fhir.read` / `fhir.write`); a token is never a staff login.
 */
class IntegrationPartner extends Model implements AuthenticatableContract
{
    use Authenticatable, HasApiTokens, HasFactory, HasPublicId;

    protected $table = 'integration_partners';

    protected $guarded = ['id'];

    protected $casts = ['settings' => 'array', 'requires_consent' => 'boolean'];

    protected static function newFactory(): IntegrationPartnerFactory
    {
        return IntegrationPartnerFactory::new();
    }

    /** @return HasMany<IntegrationCredential, $this> */
    public function credentials(): HasMany
    {
        return $this->hasMany(IntegrationCredential::class, 'partner_id');
    }

    /** @return HasMany<IntegrationEndpoint, $this> */
    public function endpoints(): HasMany
    {
        return $this->hasMany(IntegrationEndpoint::class, 'partner_id');
    }

    /** @return HasMany<IntegrationMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(IntegrationMessage::class, 'partner_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
