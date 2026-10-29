<?php

namespace App\Http\Middleware;

use App\Interop\Fhir\FhirActor;
use App\Interop\Fhir\OperationOutcome;
use App\Models\Integration\IntegrationPartner;
use App\Models\User;
use App\Services\ModuleManager;
use App\Services\TenantContextResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Who may call /api/v1/fhir: an outside partner with a client token that carries `fhir.read` (and `fhir.write` to write), or a staff member holding `interoperability.fhir.read`. A patient's
 * mobile token is neither. The hospital comes from the partner or the staff member's context — never from a header the caller sends. Refusals are OperationOutcome.
 */
class FhirAuthenticate
{
    public function __construct(private readonly TenantContextResolver $tenant, private readonly ModuleManager $modules) {}

    public function handle(Request $request, Closure $next, string $need = 'read'): Response
    {
        $who = Auth::guard('sanctum')->user();
        $actor = null;
        if ($who instanceof IntegrationPartner) {
            if (! $who->isActive()) {
                return $this->deny(403, 'forbidden', 'This partner is not active.');
            }
            $token = $who->currentAccessToken();
            $scope = $need === 'dicom' ? 'dicom.read' : ($need === 'write' ? 'fhir.write' : 'fhir.read');
            if (! $token->can($scope)) {
                return $this->deny(403, 'forbidden', "This token does not carry the scope $scope.");
            }
            $actor = new FhirActor($who->organization_id, $who, null, $token->can('fhir.write') || $token->can('dicom.write'));
        } elseif ($who instanceof User) {
            $org = $this->tenant->getOrganizationId() ?? (int) $who->organizations()->value('organizations.id');
            if (! $who->can(match ($need) {
                'write' => 'interoperability.fhir.write', 'dicom' => 'interoperability.dicom.worklist.view', default => 'interoperability.fhir.read'
            }) || ! $who->organizations()->where('organizations.id', $org)->exists()) {
                return $this->deny(403, 'forbidden', 'You do not have permission to use the FHIR API.');
            }
            $actor = new FhirActor($org, null, $who, $who->can('interoperability.fhir.write') || $who->can('interoperability.dicom.worklist.manage'));
        } else {
            return $this->deny(401, 'login', 'A client token or a staff login is needed.');
        }
        $this->tenant->setOrganizationId($actor->organizationId);
        if (! $this->modules->isEnabled('interop', $actor->organizationId)) {
            return $this->deny(404, 'not-found', 'Interoperability is not switched on.');
        }
        Auth::shouldUse('web');
        Auth::guard('web')->forgetUser();
        $request->attributes->set('fhir_actor', $actor);

        return $next($request);
    }

    private function deny(int $status, string $code, string $text): Response
    {
        return response()->json(OperationOutcome::error($code, $text), $status, ['Content-Type' => 'application/fhir+json']);
    }
}
