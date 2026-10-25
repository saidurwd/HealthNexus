<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Department;
use App\Services\TenantContextResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantContextController extends Controller
{
    public function __construct(private TenantContextResolver $resolver) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::success([
            'organization_id' => $this->resolver->getOrganizationId(),
            'branch_id' => $this->resolver->getBranchId(),
            'department_id' => $this->resolver->getDepartmentId(),
            'organizations' => $user->organizations()->get(['organizations.id', 'organizations.name', 'organizations.code']),
            'branches' => $this->resolver->getOrganizationId()
                ? $user->branches()->whereHas('organization', fn ($q) => $q->where('id', $this->resolver->getOrganizationId()))->get(['branches.id', 'branches.name', 'branches.code'])
                : collect(),
            'departments' => $this->resolver->getBranchId()
                ? Department::where('branch_id', $this->resolver->getBranchId())->get(['departments.id', 'departments.name', 'departments.code'])
                : collect(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        $user = $request->user();

        if (isset($validated['organization_id'])) {
            abort_if(! $user->organizations()->where('organizations.id', $validated['organization_id'])->exists(), 403);
            $this->resolver->setOrganizationId($validated['organization_id']);
        }

        if (isset($validated['branch_id'])) {
            abort_if(! $user->branches()->where('branches.id', $validated['branch_id'])->exists(), 403);
            $this->resolver->setBranchId($validated['branch_id']);
        }

        if (isset($validated['department_id'])) {
            $department = Department::findOrFail($validated['department_id']);
            abort_if(! $user->departments()->where('departments.id', $validated['department_id'])->exists(), 403);
            $this->resolver->setDepartmentId($validated['department_id']);
        }

        return ApiResponse::success([
            'organization_id' => $this->resolver->getOrganizationId(),
            'branch_id' => $this->resolver->getBranchId(),
            'department_id' => $this->resolver->getDepartmentId(),
        ], 'Tenant context updated successfully');
    }
}
