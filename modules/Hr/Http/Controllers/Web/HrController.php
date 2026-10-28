<?php

namespace Modules\Hr\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrJob;
use App\Services\TenantContextResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Shared guards for the HR screens: a record of another organization does not exist (404); a missing permission is 403. Forms send public ids; the server
 * turns them into records of THIS organization. Sensitive values (national id, bank account, salary) are only shown to those who hold the matching permission.
 */
abstract class HrController extends Controller
{
    public function __construct(protected readonly TenantContextResolver $tenant) {}

    protected function allow(Request $request, string $permission): void
    {
        abort_unless($request->user()->can($permission), 403);
    }

    protected function scoped(Request $request, Model $record, string $permission): Model
    {
        abort_unless($record->getAttribute('organization_id') === $this->tenant->getOrganizationId(), 404);
        $this->allow($request, $permission);

        return $record;
    }

    protected function organizationId(): int
    {
        return (int) $this->tenant->getOrganizationId();
    }

    protected function findEmployee(string $publicId): HrEmployee
    {
        return HrEmployee::query()->where('organization_id', $this->organizationId())->where('public_id', $publicId)->firstOrFail();
    }

    /** The employee record tied to the logged-in user (self-service), or 403 when there is none. */
    protected function me(Request $request): HrEmployee
    {
        $employee = HrEmployee::query()->where('organization_id', $this->organizationId())->where('user_id', $request->user()->id)->first();
        abort_if($employee === null, 403, __('Your login is not linked to an employee record. Ask HR.'));

        return $employee;
    }

    protected function departmentChoices()
    {
        return Department::query()->where('organization_id', $this->organizationId())->orderBy('name')->get(['id', 'name']);
    }

    protected function jobChoices()
    {
        return HrJob::query()->where('organization_id', $this->organizationId())->where('status', 'active')->orderBy('title')->get(['id', 'code', 'title']);
    }

    protected function employeeChoices(bool $workingOnly = true)
    {
        return HrEmployee::query()->where('organization_id', $this->organizationId())->when($workingOnly, fn ($q) => $q->whereIn('status', ['active', 'on_leave']))->orderBy('first_name')->get(['id', 'public_id', 'employee_no', 'first_name', 'last_name']);
    }

    /** Masks all but the last four characters. */
    public static function mask(?string $value): string
    {
        return $value === null || $value === '' ? '—' : str_repeat('•', max(0, mb_strlen($value) - 4)).mb_substr($value, -4);
    }
}
