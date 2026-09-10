<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request, Company $company): JsonResponse
    {
        $this->authorize('view', $company);

        $branches = Branch::query()
            ->where('company_id', $company->id)
            ->get();

        return response()->json(['data' => $branches]);
    }

    public function store(Request $request, Company $company): JsonResponse
    {
        $this->authorize('view', $company);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'slug' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $branch = $company->branches()->create($validated);

        return response()->json(['data' => $branch], 201);
    }

    public function show(Request $request, Company $company, Branch $branch): JsonResponse
    {
        $this->authorize('view', $company);

        abort_if($branch->company_id !== $company->id, 404);

        return response()->json(['data' => $branch]);
    }

    public function update(Request $request, Company $company, Branch $branch): JsonResponse
    {
        $this->authorize('view', $company);

        abort_if($branch->company_id !== $company->id, 404);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $branch->update($validated);

        return response()->json(['data' => $branch]);
    }

    public function destroy(Request $request, Company $company, Branch $branch): JsonResponse
    {
        $this->authorize('view', $company);

        abort_if($branch->company_id !== $company->id, 404);

        $branch->delete();

        return response()->json(null, 204);
    }
}
