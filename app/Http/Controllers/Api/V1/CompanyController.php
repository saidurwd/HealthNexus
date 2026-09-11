<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $companies = Company::query()
            ->when($user->hasRole('super_admin'), fn ($q) => $q, fn ($q) => $q->whereHas('users', fn ($q2) => $q2->where('user_id', $user->id)))
            ->get();

        return ApiResponse::success($companies);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:companies,code'],
            'slug' => ['required', 'string', 'max:255', 'unique:companies,slug'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $company = Company::create($validated);

        return ApiResponse::success($company, 'Company created successfully', 201);
    }

    public function show(Request $request, Company $company): JsonResponse
    {
        $this->authorize('view', $company);

        return ApiResponse::success($company);
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        $this->authorize('update', $company);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50', 'unique:companies,code,'.$company->id],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:companies,slug,'.$company->id],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $company->update($validated);

        return ApiResponse::success($company, 'Company updated successfully');
    }

    public function destroy(Request $request, Company $company): JsonResponse
    {
        $this->authorize('delete', $company);

        $company->delete();

        return ApiResponse::success(null, 'Company deleted successfully');
    }
}
