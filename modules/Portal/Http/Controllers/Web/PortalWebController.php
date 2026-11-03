<?php

namespace Modules\Portal\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Services\Portal\PortalAccess;
use Illuminate\Http\Request;

/** The patient's own pages. "Who am I looking at" is the signed-in person or, if they chose it, a relative they are approved for; every page asks for the part (scope) it needs. */
abstract class PortalWebController extends Controller
{
    public function __construct(protected readonly PortalAccess $access) {}

    protected function account(Request $request): PatientPortalAccount
    {
        return $request->attributes->get('portal_account');
    }

    protected function subject(Request $request, string $scope): Patient
    {
        return $this->access->patientFor($this->account($request), $request->session()->get('portal_for'), $scope);
    }

    /** @return list<int> */
    protected function ids(Request $request, string $scope): array
    {
        $for = $request->session()->get('portal_for');

        return $for ? [$this->subject($request, $scope)->id] : $this->access->patientIds($this->account($request), $scope);
    }
}
