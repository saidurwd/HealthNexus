<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request, Company $company): JsonResponse
    {
        $this->authorize('view', $company);

        $query = User::query()
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $company->id));

        if ($request->filled('branch_id')) {
            $query->whereHas('branches', fn ($q) => $q->where('branches.id', $request->branch_id));
        }

        if ($request->filled('department_id')) {
            $query->whereHas('departments', fn ($q) => $q->where('departments.id', $request->department_id));
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        $users = $query->get(['users.id', 'users.name', 'users.email', 'users.phone', 'users.avatar', 'users.timezone', 'users.locale', 'users.is_active', 'users.created_at']);

        return ApiResponse::success($users);
    }

    public function show(Request $request, Company $company, User $user): JsonResponse
    {
        $this->authorize('view', $company);

        abort_if(! $user->companies()->where('companies.id', $company->id)->exists(), 404);

        return ApiResponse::success($user);
    }

    public function update(Request $request, Company $company, User $user): JsonResponse
    {
        $this->authorize('view', $company);

        abort_if(! $user->companies()->where('companies.id', $company->id)->exists(), 404);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'avatar' => ['nullable', 'string', 'max:500'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'locale' => ['nullable', 'string', 'max:10'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $user->update($validated);

        return ApiResponse::success($user, 'User updated successfully');
    }
}
