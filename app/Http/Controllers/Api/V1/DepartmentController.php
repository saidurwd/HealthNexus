<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request, Company $company, Branch $branch): JsonResponse
    {
        $this->authorize('view', $company);

        abort_if($branch->company_id !== $company->id, 404);

        $departments = Department::query()
            ->where('company_id', $company->id)
            ->where('branch_id', $branch->id)
            ->get();

        return ApiResponse::success($departments);
    }

    public function store(Request $request, Company $company, Branch $branch): JsonResponse
    {
        $this->authorize('view', $company);

        abort_if($branch->company_id !== $company->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'head_of_department' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $department = $company->departments()->create(array_merge($validated, ['branch_id' => $branch->id]));

        return ApiResponse::success($department, 'Department created successfully', 201);
    }

    public function show(Request $request, Company $company, Branch $branch, Department $department): JsonResponse
    {
        $this->authorize('view', $company);

        abort_if($branch->company_id !== $company->id, 404);
        abort_if($department->branch_id !== $branch->id, 404);

        return ApiResponse::success($department);
    }

    public function update(Request $request, Company $company, Branch $branch, Department $department): JsonResponse
    {
        $this->authorize('view', $company);

        abort_if($branch->company_id !== $company->id, 404);
        abort_if($department->branch_id !== $branch->id, 404);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'head_of_department' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $department->update($validated);

        return ApiResponse::success($department, 'Department updated successfully');
    }

    public function destroy(Request $request, Company $company, Branch $branch, Department $department): JsonResponse
    {
        $this->authorize('view', $company);

        abort_if($branch->company_id !== $company->id, 404);
        abort_if($department->branch_id !== $branch->id, 404);

        $department->delete();

        return ApiResponse::success(null, 'Department deleted successfully');
    }
}
