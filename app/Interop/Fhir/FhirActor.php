<?php

namespace App\Interop\Fhir;

use App\Models\Integration\IntegrationPartner;
use App\Models\User;

/** Who is calling the FHIR API: an outside partner (token with scopes, consent applies) or a staff member (permission applies). */
final class FhirActor
{
    public function __construct(public readonly int $organizationId, public readonly ?IntegrationPartner $partner = null, public readonly ?User $user = null, public readonly bool $canWrite = false) {}
}
