<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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

        return response()->json([
            'data' => [
                'company_id' => $this->resolver->getCompanyId(),
                'branch_id' => $this->resolver->getBranchId(),
                'department_id' => $this->resolver->getDepartmentId(),
                'companies' => $user->companies()->get(['companies.id', 'companies.name', 'companies.code']),
                'branches' => $this->resolver->getCompanyId()
                    ? $user->branches()->whereHas('company', fn ($q) => $q->where('id', $this->resolver->getCompanyId()))->get(['branches.id', 'branches.name', 'branches.code'])
                    : collect(),
                'departments' => $this->resolver->getBranchId()
                    ? Department::where('branch_id', $this->resolver->getBranchId())->get(['departments.id', 'departments.name', 'departments.code'])
                    : collect(),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        $user = $request->user();

        if (isset($validated['company_id'])) {
            abort_if(! $user->companies()->where('companies.id', $validated['company_id'])->exists(), 403);
            $this->resolver->setCompanyId($validated['company_id']);
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

        return response()->json([
            'data' => [
                'company_id' => $this->resolver->getCompanyId(),
                'branch_id' => $this->resolver->getBranchId(),
                'department_id' => $this->resolver->getDepartmentId(),
            ],
        ]);
    }
}
