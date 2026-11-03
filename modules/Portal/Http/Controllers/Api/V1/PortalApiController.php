<?php

namespace Modules\Portal\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Services\Portal\PortalAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Envelope {success,message,data,meta} and the one way to find "whose record is this" for a patient login (own record or an approved relative's, by scope). */
abstract class PortalApiController extends Controller
{
    public function __construct(protected readonly PortalAccess $access) {}

    protected function ok(mixed $data = null, string $message = 'OK', array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data, 'meta' => $meta], $status);
    }

    protected function account(Request $request): PatientPortalAccount
    {
        return $request->attributes->get('portal_account');
    }

    /** The patient named by ?patient=<public id> (default: the login's own), if the login may open them for this scope. */
    protected function subject(Request $request, string $scope): Patient
    {
        return $this->access->patientFor($this->account($request), $request->input('patient'), $scope);
    }

    /** @return list<int> */
    protected function ids(Request $request, string $scope): array
    {
        $ids = $this->access->patientIds($this->account($request), $scope);
        $named = $request->input('patient');
        if ($named) {
            return [$this->access->patientFor($this->account($request), $named, $scope)->id];
        }

        return $ids;
    }

    protected function page(Request $request): int
    {
        return max(1, min((int) config('portal.api.max_page_size', 50), (int) $request->input('per_page', config('portal.api.page_size', 20))));
    }
}
