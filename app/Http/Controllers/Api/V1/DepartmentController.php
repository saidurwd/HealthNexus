<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request, Organization $organization, Branch $branch): JsonResponse
    {
        $this->authorize('view', $organization);

        abort_if($branch->organization_id !== $organization->id, 404);

        $departments = Department::query()
            ->where('organization_id', $organization->id)
            ->where('branch_id', $branch->id)
            ->get();

        return ApiResponse::success($departments);
    }

    public function store(Request $request, Organization $organization, Branch $branch): JsonResponse
    {
        $this->authorize('view', $organization);

        abort_if($branch->organization_id !== $organization->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'head_of_department' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $department = $organization->departments()->create(array_merge($validated, ['branch_id' => $branch->id]));

        return ApiResponse::success($department, 'Department created successfully', 201);
    }

    public function show(Request $request, Organization $organization, Branch $branch, Department $department): JsonResponse
    {
        $this->authorize('view', $organization);

        abort_if($branch->organization_id !== $organization->id, 404);
        abort_if($department->branch_id !== $branch->id, 404);

        return ApiResponse::success($department);
    }

    public function update(Request $request, Organization $organization, Branch $branch, Department $department): JsonResponse
    {
        $this->authorize('view', $organization);

        abort_if($branch->organization_id !== $organization->id, 404);
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

    public function destroy(Request $request, Organization $organization, Branch $branch, Department $department): JsonResponse
    {
        $this->authorize('view', $organization);

        abort_if($branch->organization_id !== $organization->id, 404);
        abort_if($department->branch_id !== $branch->id, 404);

        $department->delete();

        return ApiResponse::success(null, 'Department deleted successfully');
    }
}
