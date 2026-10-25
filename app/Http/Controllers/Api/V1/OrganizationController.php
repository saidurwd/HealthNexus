<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $organizations = Organization::query()
            ->when($user->hasRole('super_admin'), fn ($q) => $q, fn ($q) => $q->whereHas('users', fn ($q2) => $q2->where('user_id', $user->id)))
            ->get();

        return ApiResponse::success($organizations);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:organizations,code'],
            'slug' => ['required', 'string', 'max:255', 'unique:organizations,slug'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $organization = Organization::create($validated);

        return ApiResponse::success($organization, 'Organization created successfully', 201);
    }

    public function show(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize('view', $organization);

        return ApiResponse::success($organization);
    }

    public function update(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize('update', $organization);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50', 'unique:organizations,code,'.$organization->id],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:organizations,slug,'.$organization->id],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $organization->update($validated);

        return ApiResponse::success($organization, 'Organization updated successfully');
    }

    public function destroy(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize('delete', $organization);

        $organization->delete();

        return ApiResponse::success(null, 'Organization deleted successfully');
    }
}
