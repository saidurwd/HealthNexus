<?php

namespace Modules\Welfare\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\TenantContextResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Shared guards for the welfare screens: another hospital's record does not exist (404); a missing right is 403; the hospital is always the signed-in user's. */
abstract class WelfareController extends Controller
{
    public function __construct(protected readonly TenantContextResolver $tenant) {}

    protected function allow(Request $request, string ...$permissions): void
    {
        abort_unless(collect($permissions)->contains(fn ($p) => $request->user()->can($p)), 403);
    }

    protected function organizationId(): int
    {
        return (int) $this->tenant->getOrganizationId();
    }

    /**
     * @template T of Model
     *
     * @param  class-string<T>  $class
     * @return T
     */
    protected function find(string $class, string $publicId): Model
    {
        return $class::query()->where('public_id', $publicId)->where('organization_id', $this->organizationId())->firstOrFail();
    }

    protected function patientByNumber(string $number): Patient
    {
        return Patient::query()->where('organization_id', $this->organizationId())->where('enterprise_patient_no', trim($number))->first() ?? throw ValidationException::withMessages(['patient_no' => __('No patient has that number.')]);
    }
}
