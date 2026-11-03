<?php

namespace Modules\Interop\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interop\Fhir\FhirActor;
use App\Interop\Fhir\FhirValidator;
use App\Interop\Fhir\OperationOutcome;
use App\Services\Interop\FhirService;
use App\Services\Interop\InteropException;
use App\Services\Interop\TerminologyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The FHIR R4 REST API: `GET /api/v1/fhir/Patient/{id}`, searches, `$everything`, `$validate`, `$lookup`, `$translate`. Every answer — including every error — is FHIR JSON. */
class FhirController extends Controller
{
    public function __construct(private readonly FhirService $fhir, private readonly FhirValidator $validator, private readonly TerminologyService $terminology) {}

    private function actor(Request $request): FhirActor
    {
        return $request->attributes->get('fhir_actor');
    }

    private function json(array $body, int $status = 200, array $headers = []): JsonResponse
    {
        return response()->json($body, $status, ['Content-Type' => 'application/fhir+json'] + $headers);
    }

    private function guarded(callable $fn): JsonResponse
    {
        try {
            return $fn();
        } catch (InteropException $e) {
            return $this->json(OperationOutcome::error($e->code_, $e->getMessage()), $e->status);
        }
    }

    public function metadata(): JsonResponse
    {
        return $this->json($this->fhir->capability());
    }

    public function read(Request $request, string $type, string $id): JsonResponse
    {
        return $this->guarded(fn () => $this->json($this->fhir->read($this->actor($request), $type, $id)));
    }

    public function search(Request $request, string $type): JsonResponse
    {
        return $this->guarded(fn () => $this->json($this->fhir->search($this->actor($request), $type, $request->query(), $request->fullUrl())));
    }

    public function create(Request $request, string $type): JsonResponse
    {
        return $this->guarded(function () use ($request, $type) {
            if ($type !== 'Patient') {
                throw new InteropException("Creating $type is not supported; Patient is the only resource this server accepts.", 405, 'not-supported');
            }
            [$resource, $created] = $this->fhir->writePatient($this->actor($request), (array) $request->json()->all());

            return $this->json($resource, $created ? 201 : 200, ['Location' => url('/api/v1/fhir/Patient/'.$resource['id'])]);
        });
    }

    public function update(Request $request, string $type, string $id): JsonResponse
    {
        return $this->guarded(function () use ($request, $type, $id) {
            if ($type !== 'Patient') {
                throw new InteropException("Updating $type is not supported.", 405, 'not-supported');
            }
            [$resource] = $this->fhir->writePatient($this->actor($request), (array) $request->json()->all(), $id);

            return $this->json($resource);
        });
    }

    public function everything(Request $request, string $type, string $id): JsonResponse
    {
        return $this->guarded(function () use ($request, $type, $id) {
            return match ($type) {
                'Patient' => $this->json($this->fhir->everything($this->actor($request), $id)),
                'Encounter' => $this->json($this->fhir->encounterBundle($this->actor($request), $id)),
                default => throw new InteropException('$everything is available for Patient and Encounter.', 404, 'not-supported'),
            };
        });
    }

    public function validateResource(Request $request, ?string $type = null): JsonResponse
    {
        return $this->guarded(function () use ($request, $type) {
            $resource = (array) $request->json()->all();
            if (($resource['resourceType'] ?? null) === 'Parameters') { // FHIR wraps the resource in Parameters
                $wrapped = collect($resource['parameter'] ?? [])->firstWhere('name', 'resource');
                $resource = (array) ($wrapped['resource'] ?? []);
            }
            if ($type !== null) {
                $this->fhir->mapper($type);
            }
            $issues = $this->validator->validate($resource, $this->actor($request)->partner->profile ?? 'r4');

            return $issues === [] ? $this->json(OperationOutcome::ok('The resource passes the checks this server makes.')) : $this->json(OperationOutcome::make($issues), 422);
        });
    }

    public function lookup(Request $request): JsonResponse
    {
        return $this->guarded(function () use ($request) {
            $request->validate(['system' => ['required', 'string'], 'code' => ['required', 'string']]);
            $found = $this->terminology->lookup($request->query('system'), $request->query('code'));
            if ($found === null) {
                throw new InteropException('That code is not in the registry.', 404, 'not-found');
            }

            $parameters = [['name' => 'name', 'valueString' => $found['system']], ['name' => 'display', 'valueString' => $found['display']]];
            if ($found['display_bn']) {
                $parameters[] = ['name' => 'designation', 'part' => [['name' => 'language', 'valueCode' => 'bn'], ['name' => 'value', 'valueString' => $found['display_bn']]]];
            }

            return $this->json(['resourceType' => 'Parameters', 'parameter' => $parameters]);
        });
    }

    public function translate(Request $request): JsonResponse
    {
        return $this->guarded(function () use ($request) {
            $request->validate(['system' => ['required', 'string'], 'code' => ['required', 'string']]);
            $matches = $this->terminology->translate($request->query('system'), $request->query('code'), $request->query('target'));

            return $this->json(['resourceType' => 'Parameters', 'parameter' => array_merge([['name' => 'result', 'valueBoolean' => $matches !== []]], array_map(fn ($m) => ['name' => 'match', 'part' => [
                ['name' => 'equivalence', 'valueCode' => $m['relationship']], ['name' => 'concept', 'valueCoding' => ['system' => $m['system'], 'code' => $m['code']]]]], $matches))]);
        });
    }
}
